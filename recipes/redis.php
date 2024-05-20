<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/redis_install', fn() => askConfirmation(' Do you want to install Redis? '));

set('phpionner/redis_password', fn() => generatePassword(80));

desc('Setup Redis');
task('phpionner/redis:setup', function () {
    if (!get('phpionner/redis_install')) {
        return;
    }

    run('apt-get install -y redis-server', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run("sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf");
    run("sed -i 's/^# requirepass foobared/requirepass %secret%/' /etc/redis/redis.conf", ['secret' => get('phpionner/redis_password')]);
    info('');
    info('Redis password: ' . get('phpionner/redis_password'));
    info('');
});
