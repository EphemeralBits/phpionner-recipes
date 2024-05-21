<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\ask;
use function Deployer\askChoice;
use function Deployer\askConfirmation;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\info;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

function requestSslCertificate(
    string $domainName,
    string $serverRoot,
    bool $addWww = false,
)
{
    $domainNameWwwParam = $addWww ? ' -d www.' . $domainName : '';

    switch (get('phpionner/ssl_certbot_method')) {
        case 'Local webserver':
            run("sudo certbot certonly --webroot -w $serverRoot -d $domainName$domainNameWwwParam -m {{phpionner/ssl_certbot_email}} --agree-tos --no-eff-email");
            break;
        case 'Cloudflare':
            run("sudo certbot certonly --dns-cloudflare --dns-cloudflare-credentials /root/.secrets/cloudflare.ini --dns-cloudflare-propagation-seconds 60 -d $domainName$domainNameWwwParam -m {{phpionner/ssl_certbot_email}} --agree-tos --no-eff-email");
            break;
    }
}

set('phpionner/ssl', fn() => askConfirmation(' Do you want to setup SSL? ', true));
set('phpionner/ssl_certbot_install', fn() => askConfirmation(' Do you want to install Certbot? '));
set('phpionner/ssl_certbot_email', fn() => ask(' In which email address should Let\'s Encrypt send notifications? '));
set('phpionner/ssl_certbot_method', function () {
    $options = [
        'Local webserver',
        'Cloudflare',
    ];
    return askChoice(' Which challenge method should be used for Let\'s Encrypt? ', $options, 0);
});

set('phpionner/cloudflare', fn() => askConfirmation(' Do you want to configure Cloudflare API token? ', false));
set('phpionner/cloudflare_token', function () {
    info('To use Cloudflare API, you need to create a token (https://dash.cloudflare.com/profile/api-tokens) with the following permissions: Zone:DNS:Edit');
    return ask(' What is your Cloudflare API token? ');
});

desc('Setup Certbot');
task('phpionner/ssl:setup', function () {
    if (!get('phpionner/ssl_certbot_install')) {
        return;
    }

    run('snap install --classic certbot', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run('snap set certbot trust-plugin-with-root=ok');
    run('snap install certbot-dns-cloudflare');
    run("mkdir ~/.secrets");

    if (get('phpionner/cloudflare')) {
        run("echo 'dns_cloudflare_api_token = {{phpionner/cloudflare_token}}' >> ~/.secrets/cloudflare.ini");
        run("chmod 600 ~/.secrets/cloudflare.ini");
    }

    run("mkdir -p /etc/letsencrypt/renewal-hooks/deploy");
    run("echo -e '#!/bin/bash\n\nsystemctl reload nginx' >> /etc/letsencrypt/renewal-hooks/deploy/reload_nginx.sh");
    run('chmod u+x /etc/letsencrypt/renewal-hooks/deploy/reload_nginx.sh');
})->oncePerNode();
