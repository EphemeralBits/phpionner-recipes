<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\askChoice;
use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;

set('phpionner/laravel', fn() => askConfirmation(' Is this a Laravel site? ', true));
set('phpionner/laravel_schedule', fn() => askConfirmation(' Should Laravel\'s schedule be configured? ', true));
set('phpionner/laravel_queue', function () {
    $options = [
        'none',
        'queue worker',
        'horizon',
    ];
    return askChoice(' Which queue system should be used? ', $options, 0);
});

desc('Setup Schedule');
task('phpionner/laravel:schedule:setup', function () {
    if (!get('phpionner/laravel_schedule')) {
        return;
    }

    $command = 'cd /home/{{phpionner/user_name}}/{{phpionner/domain_name}}/current/artisan && php artisan schedule:run';

    if (test("[[ $(crontab -l | egrep -v \"^(#|$)\" | grep -q '$command'; echo $?) == 1 ]]")) {
        run("(crontab -l ; echo \"* * * * * $command >> /dev/null 2>&1\")| crontab -");
    }
})->oncePerNode();

desc('Setup Queue');
task('phpionner/laravel:queue:setup', function () {
    switch (get('phpionner/laravel_queue')) {
        case 'queue worker':
            $name = 'queue';
            $command = 'php artisan queue:work --queue=default --tries=3';
            break;
        case 'horizon':
            $name = 'horizon';
            $command = 'php artisan horizon';
            break;
        default:
            return;
    }

    set('phpionner/laravel_queue_name', $name);
    set('phpionner/laravel_queue_command', $command);
    $config = loadTemplate('supervisor_queue.tmpl');

    run('mkdir -p /home/{{phpionner/user_name}}/{{phpionner/domain_name}}/shared/storage/logs');
    run("touch /home/{{phpionner/user_name}}/{{phpionner/domain_name}}/shared/storage/logs/{{phpionner/laravel_queue_name}}.log");
    run("sudo sh -c 'echo \"$config\" > /etc/supervisor/conf.d/{{phpionner/domain_name}}-{{phpionner/laravel_queue_name}}.conf'");
    run("sudo supervisorctl reread");
    run("sudo supervisorctl update");
    info("Queue daemon is installed but not started. Run `dep laravel:queue:start` to start it.");
})->oncePerNode();

desc('Setup Queue');
task('phpionner/laravel:queue:start', function () {
    switch (get('phpionner/laravel_queue')) {
        case 'queue worker':
            $name = 'queue';
            break;
        case 'horizon':
            $name = 'horizon';
            break;
        default:
            return;
    }

    run("sudo supervisorctl start {{phpionner/domain_name}}-$name");
})->oncePerNode();
