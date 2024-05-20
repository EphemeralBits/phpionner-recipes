<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\askChoice;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/php_version', function () {
    $versions = [
        '8.3' => 'PHP 8.3',
        '8.2' => 'PHP 8.2',
    ];
    return askChoice(' Which PHP version do you want to install? ', $versions, '8.3');
});

set('phpionner/php_installed_version', fn() => run("php -r 'echo PHP_MAJOR_VERSION . \".\" . PHP_MINOR_VERSION;'"));

desc('Add PHP repository');
task('phpionner/php:repository', function () {
    // PHP
    run('apt-add-repository ppa:ondrej/php -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
});

desc('Setup PHP');
task('phpionner/php:setup', function () {
    $version = get('phpionner/php_version');
    $packages = [
        "php$version-bcmath",
        "php$version-cli",
        "php$version-curl",
        "php$version-fpm",
        "php$version-gd",
        "php$version-intl",
        "php$version-mbstring",
        "php$version-mysql",
        "php$version-pgsql",
        "php$version-redis",
        "php$version-soap",
        "php$version-sqlite3",
        "php$version-xml",
        "php$version-zip",
    ];
    run('apt-get install -y ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);

    // Configure PHP-CLI
    run("sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/post_max_size = .*/post_max_size = 101M/' /etc/php/$version/cli/php.ini");
    run("sudo sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/cli/php.ini");

    // Configure PHP-FPM
    run("sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/post_max_size = .*/post_max_size = 101M/' /etc/php/$version/fpm/php.ini");
    run("sed -i 's/;date.timezone.*/date.timezone = UTC/' /etc/php/$version/fpm/php.ini");

    // Configure FPM Pool
    run("sed -i 's/= www-data/= {{phpionner/user_name}}/g' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;php_admin_value\[error_log\] = .*/php_admin_value[error_log] = \/var\/log\/fpm-php.www.log/' /etc/php/$version/fpm/pool.d/www.conf");
    run("sed -i 's/;php_admin_flag\[log_errors\] = .*/php_admin_flag[log_errors] = on/' /etc/php/$version/fpm/pool.d/www.conf");
})->oncePerNode();

desc('Setup Composer');
task('phpionner/php:composer:setup', function () {
    run('curl -sS https://getcomposer.org/installer | php');
    run('mv composer.phar /usr/local/bin/composer');
})->oncePerNode();
