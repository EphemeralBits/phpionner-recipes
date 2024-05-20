<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\ask;
use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/postgresql_install', fn() => askConfirmation(' Do you want to install PostgreSQL? '));

set('phpionner/postgres', fn() => askConfirmation(' Should a PostgreSQL database be created? ', false));

set('phpionner/postgres_name', fn() => ask('DB name: ', preparePostgreSqlName(get('phpionner/domain_name'))));

set('phpionner/postgres_user', fn() => ask(' DB user: ', preparePostgreSqlName(get('phpionner/domain_name'))));

set('phpionner/postgres_password', fn() => generatePassword());

desc('Setup PostgreSQL');
task('phpionner/postgresql:setup', function () {
    if (!get('phpionner/postgresql_install')) {
        return;
    }

    run('apt-get install -y postgresql postgresql-contrib', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
});

desc('Setup PostgreSQL');
task('phpionner/postgres:create', function () {
    if (!get('phpionner/postgres')) {
        return;
    }

    run("sudo -u postgres psql -v \"ON_ERROR_STOP=1\" <<< $'CREATE DATABASE {{phpionner/postgres_name}} TEMPLATE template0 ENCODING 'UTF8';'");
    run("sudo -u postgres psql -v \"ON_ERROR_STOP=1\" <<< $'CREATE USER {{phpionner/postgres_user}} WITH ENCRYPTED PASSWORD \'%secret%\';'", ['secret' => get('phpionner/postgres_password')]);
    run("sudo -u postgres psql -v \"ON_ERROR_STOP=1\" <<< $'ALTER DATABASE {{phpionner/postgres_name}} OWNER TO {{phpionner/postgres_user}};'");
    run("sudo -u postgres psql -v \"ON_ERROR_STOP=1\" <<< $'GRANT ALL PRIVILEGES ON DATABASE {{phpionner/postgres_name}} TO {{phpionner/postgres_user}};'");

    info('');
    info('PostgreSQL password: ' . get('postgres_password'));
    info('');
})->oncePerNode();
