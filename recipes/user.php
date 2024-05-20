<?php

namespace EphemeralBits\PhpionnerRecipes;

use Deployer\Exception\RunException;
use function Deployer\askHiddenResponse;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/user_password', fn() => generatePassword());

set('phpionner/user_sudo_pass', fn() => askHiddenResponse(' What is the sudo password for the user "{{phpionner/user_name}}"? '));

desc('Create user');
task('phpionner/user:create', function () {
    if (!empty(run('grep {{phpionner/user_name}} /etc/passwd || true'))) {
        info('User already exists, skipping');
        return;
    }

    run('useradd -m {{phpionner/user_name}}');
    run('usermod -a -G sudo {{phpionner/user_name}}');
    $password = run("mkpasswd -m sha-512 '%secret%'", ['secret' => get('phpionner/user_password')]);
    run("usermod --password '%secret%' {{phpionner/user_name}}", ['secret' => $password]);

    run('chsh -s /bin/bash {{phpionner/user_name}}');
    run("sed -i 's/#force_color_prompt=yes/force_color_prompt=yes/' /home/{{phpionner/user_name}}/.bashrc");

    run('mkdir -p /home/{{phpionner/user_name}}/.ssh');
    run('cp /root/.ssh/authorized_keys /home/{{phpionner/user_name}}/.ssh/authorized_keys');
    run('ssh-keygen -f /home/{{phpionner/user_name}}/.ssh/id_ed25519 -t ed25519 -N "" -C "{{phpionner/user_name}}@{{alias}}"');

    run('chown -R {{phpionner/user_name}}:{{phpionner/user_name}} /home/{{phpionner/user_name}}');
    run('chmod -R 755 /home/{{phpionner/user_name}}');
    run('chmod 700 /home/{{phpionner/user_name}}/.ssh/id_ed25519');

    info('');
    info('Password for sudo: ' . get('phpionner/user_password'));
    info('');
})->oncePerNode();
