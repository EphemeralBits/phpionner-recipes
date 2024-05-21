<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\ask;
use function Deployer\askHiddenResponse;
use function Deployer\desc;
use function Deployer\error;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/user_password', fn() => generatePassword());

set('phpionner/user_sudo_pass', fn() => askHiddenResponse(' What is the sudo password for the user "{{phpionner/user_name}}"? '));

set('phpionner/user_custom_name', fn() => ask(' What is the name of the user? '));
set('phpionner/user_custom_password', fn() => generatePassword());
set('phpionner/user_custom_key', fn() => ask(' What is the public key of the user? '));

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

desc('Create custom user');
task('phpionner/user:create_custom', function () {
    set('sudo_pass', get('phpionner/user_sudo_pass'));

    if (!empty(run('sudo grep {{phpionner/user_custom_name}} /etc/passwd || true'))) {
        error('User already exists!');
        return;
    }

    run('sudo useradd -g {{phpionner/user_name}} -G sudo -m {{phpionner/user_custom_name}}');
    $password = run("sudo mkpasswd -m sha-512 '%secret%'", ['secret' => get('phpionner/user_custom_password')]);
    run("sudo usermod --password '%secret%' {{phpionner/user_custom_name}}", ['secret' => $password]);
    run("sudo passwd {{phpionner/user_custom_name}} --expire");

    run('sudo chsh -s /bin/bash {{phpionner/user_custom_name}}');
    run("sudo sed -i 's/#force_color_prompt=yes/force_color_prompt=yes/' /home/{{phpionner/user_custom_name}}/.bashrc");

    run('sudo mkdir -p /home/{{phpionner/user_custom_name}}/.ssh');
    run("sudo sh -c 'echo \"{{phpionner/user_custom_key}}\" > /home/{{phpionner/user_custom_name}}/.ssh/authorized_keys'");

    run('sudo chown -R {{phpionner/user_custom_name}}:{{phpionner/user_name}} /home/{{phpionner/user_custom_name}}');

    info('');
    info('Password for {{phpionner/user_custom_name}}: {{phpionner/user_custom_password}}');
    info('');
})->oncePerNode();
