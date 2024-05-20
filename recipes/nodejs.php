<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\askChoice;
use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

set('phpionner/nodejs_install', fn() => askConfirmation(' Do you want to install Node.js? ', true));

set('phpionner/nodejs_version', function () {
    // Node.js versions from https://github.com/nodesource/distributions.
    $versions = [
        '22.x' => 'Node.js 22.x',
        '21.x' => 'Node.js 21.x',
        '20.x' => 'Node.js 20.x (LTS)',
        '18.x' => 'Node.js 18.x',
    ];
    $choice = askChoice(' Which Node.js version do you want to install? ', $versions, '20.x');

    return "node_$choice";
});

desc('Add Node.js repository');
task('phpionner/nodejs:repository', function () {
    if (get('phpionner/nodejs_install')) {
        $keyring = '/usr/share/keyrings/nodesource.gpg';
        run("curl -fsSL https://deb.nodesource.com/gpgkey/nodesource.gpg.key | gpg --dearmor | sudo tee '$keyring' >/dev/null");
        run("gpg --no-default-keyring --keyring '$keyring' --list-keys");
        run("echo 'deb [signed-by=$keyring] https://deb.nodesource.com/{{phpionner/nodejs_version}} {{phpionner/lsb_release}} main' | sudo tee /etc/apt/sources.list.d/nodesource.list");
        run("echo 'deb-src [signed-by=$keyring] https://deb.nodesource.com/{{phpionner/nodejs_version}} {{phpionner/lsb_release}} main' | sudo tee -a /etc/apt/sources.list.d/nodesource.list");
    }
});

desc('Setup Node');
task('phpionner/nodejs:setup', function () {
    if (!get('phpionner/nodejs_install')) {
        return;
    }

    run('apt-get install -y nodejs', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
})->oncePerNode();
