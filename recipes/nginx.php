<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\desc;
use function Deployer\get;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

function createSite(string $config)
{
    run("sudo sh -c 'echo \"$config\" > /etc/nginx/sites-available/{{phpionner/domain_name}}'");
    run('sudo ln -sf /etc/nginx/sites-available/{{phpionner/domain_name}} /etc/nginx/sites-enabled/');
    run('sudo nginx -t');
    run('sudo systemctl reload nginx');
}

desc('Setup NGINX');
task('phpionner/nginx:setup', function () {
    run('apt-get install -y nginx', ['env' => ['DEBIAN_FRONTEND' => 'noninteractive'], 'timeout' => 900]);
    run('curl https://ssl-config.mozilla.org/ffdhe2048.txt > /etc/nginx/dhparam.pem');
    run('unlink /etc/nginx/sites-enabled/default');
    run("sed -i 's/^user www-data;/user {{phpionner/user_name}};/g' /etc/nginx/nginx.conf");
    run("sed -i 's/# server_tokens .*/server_tokens off;/' /etc/nginx/nginx.conf");
    run("sed -i -E 's/^([[:blank:]]*)(# *)(gzip_(vary|proxied|comp_level|buffers|http_version|types))/\\1\\3/' /etc/nginx/nginx.conf");
})->oncePerNode();

task('phpionner/nginx:test', function () {
    set('phpionner/server_root', "/home/{{phpionner/user_name}}/{{phpionner/domain_name}}/current");
    $vhost = loadTemplate('nginx_https.tmpl');

    run("sudo sh -c 'echo \"$vhost\" > /etc/nginx/sites-available/{{phpionner/domain_name}}'");
});

desc('Create site');
task('phpionner/nginx:site', function () {
    run('mkdir -p /home/{{phpionner/user_name}}/{{phpionner/domain_name}}');
    run('chmod -R u=rwX,g=rX,o= /home/{{phpionner/user_name}}/{{phpionner/domain_name}}');

    $serverRoot = get('phpionner/laravel') ? "/home/{{phpionner/user_name}}/{{phpionner/domain_name}}/current/public" : "/home/{{phpionner/user_name}}/{{phpionner/domain_name}}/current";
    set('phpionner/server_root', parse($serverRoot));

    if (!get('phpionner/ssl')) {
        $config = get('phpionner/domain_name_www') ? loadTemplate('nginx_http_www.tmpl') : loadTemplate('nginx_http.tmpl');
        createSite($config);
        return;
    }

    if (get('phpionner/ssl_certbot_method') == 'Local webserver') {
        $config = loadTemplate('nginx_http_issue_ssl.tmpl');
        createSite($config);
    }

    requestSslCertificate(
        get('phpionner/domain_name'),
        $serverRoot,
        addWww: get('phpionner/domain_name_www'),
    );

    $config = get('phpionner/domain_name_www') ? loadTemplate('nginx_https_www.tmpl') : loadTemplate('nginx_https.tmpl');
    createSite($config);
})->oncePerNode();
