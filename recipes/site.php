<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\ask;
use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\set;
use function Deployer\task;

set('phpionner/domain_name', fn() => ask(' What is the domain name for the site? '));
set('phpionner/domain_name_www', fn() => askConfirmation(' Should the www subdomain be configured? '));

desc('Create site');
task('phpionner/site:create', [
    'phpionner/site:create:wizard',
    'phpionner/nginx:site',
    'phpionner/postgres:create',
    'phpionner/laravel:schedule:setup',
    'phpionner/laravel:queue:setup',
]);

desc('Site wizard');
task('phpionner/site:create:wizard', function () {
    $parameters = [
        'phpionner/user_sudo_pass',
        'phpionner/domain_name',
        'phpionner/domain_name_www',
        'phpionner/ssl' => [
            'phpionner/ssl_certbot_email',
            'phpionner/ssl_certbot_method',
        ],
        'phpionner/laravel' => [
            'phpionner/laravel_schedule',
            'phpionner/laravel_queue',
        ],
        'phpionner/postgres' => [
            'phpionner/postgres_name',
            'phpionner/postgres_user',
        ],
    ];

    askConfigurationParameters($parameters);
    set('sudo_pass', get('phpionner/user_sudo_pass'));
});
