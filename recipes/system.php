<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\ask;
use function Deployer\desc;
use function Deployer\error;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/lsb_release', fn() => run("lsb_release -s -c"));

set('phpionner/floating_ip', fn() => ask(' Floating IP: '));

desc('Check pre-required system state');
task('phpionner/system:check', function () {
    if (get('remote_user') !== 'root') {
        throw error('Run provision as root: -o remote_user=root');
    }

    $release = run('cat /etc/os-release');
    ['NAME' => $name, 'VERSION_ID' => $version] = parse_ini_string($release);
    if ($name !== 'Ubuntu' || $version !== '22.04') {
        throw error('Only Ubuntu 22.04 LTS is supported!');
    }
})->oncePerNode();

desc('Add repositories, update repositories, upgrade all packges and install common packages');
task('phpionner/system:prepare', [
    'phpionner/php:repository',
    'phpionner/nodejs:repository',
    'phpionner/system:update',
    'phpionner/system:upgrade',
    'phpionner/system:install-common',
]);

desc('Update repositories');
task('phpionner/system:update', function () {
    run('apt-get update', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive']]);
})->oncePerNode();

desc('Upgrade all packages');
task('phpionner/system:upgrade', function () {
    run('apt-get upgrade -y', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
})->oncePerNode();

desc('Install common packages');
task('phpionner/system:install-common', function () {
    $packages = [
        'acl',
        'supervisor',
        'unzip',
        'whois',
    ];
    run('apt-get install -y ' . implode(' ', $packages), ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
})->oncePerNode();

desc('SSH wizard');
task('phpionner/system:ssh:wizard', function () {
    run("sed -i -E 's/#?PermitRootLogin .*/PermitRootLogin no/' /etc/ssh/sshd_config");
    run("sed -i -E 's/#?PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config");
    run("sed -i 's/#Banner none/#Banner none\\nDebianBanner no/' /etc/ssh/sshd_config");
    run('ssh-keygen -A');
    run('service ssh restart');
    info('Root and password authentication disabled. Please use SSH key and user {{phpionner/user_name}} to login.');
})->oncePerNode();

desc('Configure persistent floating IP');
task('phpionner/system:floating-ip', function () {
    $config = loadTemplate('netplan_floating_ip.tmpl');
    run("sudo sh -c 'echo \"$config\" > /etc/netplan/60-floating-ip.yaml'");
    run('sudo netplan apply');
})->oncePerNode();
