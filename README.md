# PHPionner recipes

[PHPionner](https://github.com/EphemeralBits/phpioneer) opinionated recipes for quick and easy PHP server configuration for [Deployer](https://deployer.org/).

You can use [PHPionner](https://github.com/EphemeralBits/phpioneer) or require these recipes directly into your project.

It installs:

- PHP 8.2 or 8.3
- Nginx
- PostgreSQL
- Redis
- Supervisor
- Node.js 22, 21, 20 or 18
- Certbot

Also, it can do some common tasks:

- Create sites
- Create users
- Configure a floating IP
- Set up Laravel schedule and queues
- Set up SSL certificates via Let's Encrypt

## Getting Started

```shell
composer require ephemeralbits/phpionner-recipes
./vendor/bin/dep init
```

Modify `deploy.php` adding:

```php
// Add this after the require statements
require './vendor/ephemeralbits/phpionner-recipes/recipes/autoload.php';
// Add this after set() calls
set('phpionner/user_name', 'deployer');
```

> Replace `deployer` for your desired username.

## Configure a new server

Configures a new server to serve PHP projects. It requires Ubuntu 22.04 LTS and a root user.

```shell
./vendor/bin/dep phpionner/configure -o remote_user=root
```

## Create a new site

```shell
./vendor/bin/dep phpionner/site:create
```
