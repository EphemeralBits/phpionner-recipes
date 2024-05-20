<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\info;
use function Deployer\run;
use function Deployer\task;

desc('Configure the server');
task('phpionner/configure', [
    'phpionner/system:check',
    'phpionner/configure:wizard',
    'phpionner/system:prepare',
    'phpionner/user:create',
    'phpionner/nginx:setup',
    'phpionner/php:setup',
    'phpionner/php:composer:setup',
    'phpionner/nodejs:setup',
    'phpionner/ssl:setup',
    'phpionner/postgresql:setup',
    'phpionner/redis:setup',
    'phpionner/system:ssh:wizard',
    'phpionner/configure:finish',
]);

desc('Configure wizard');
task('phpionner/configure:wizard', function () {
    $parameters = [
        'phpionner/php_version',
        'phpionner/nodejs_install' => ['phpionner/nodejs_version'],
        'phpionner/ssl_certbot_install',
        'phpionner/cloudflare' => ['phpionner/cloudflare_token'],
        'phpionner/postgresql_install',
        'phpionner/redis_install',
    ];

    askConfigurationParameters($parameters);
});

desc('Finish');
task('phpionner/configure:finish', function () {
    info('Configuring finished');
    info('The server is ready to use');

    if (askConfirmation('Do you want to reboot the server?', true)) {
        run('reboot');
    }
})->oncePerNode();
