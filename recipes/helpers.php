<?php

namespace EphemeralBits\PhpionnerRecipes;

use function Deployer\get;
use function Deployer\parse;
use function Deployer\run;

function askConfigurationParameters(array $parameters): void
{
    foreach ($parameters as $parameter => $subParameters) {
        if (is_int($parameter)) {
            $parameter = $subParameters;
            $subParameters = [];
        }

        $result = get($parameter);

        if (count($subParameters) > 0 && $result === true) {
            askConfigurationParameters($subParameters);
        }
    }
}

function aptGetInstall(array $packages): void
{
    run(
        'apt-get install -y ' . implode(' ', $packages),
        [
            'env' => ['DEBIAN_FRONTEND' => 'noninteractive'],
            'timeout' => 900,
        ],
    );
}

function generatePassword(int $length = 20): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%*-_=+.';
    $charLength = strlen($characters) - 1;

    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $randomIndex = random_int(0, $charLength);
        $password .= $characters[$randomIndex];
    }

    return $password;
}

function preparePostgreSqlName(string $name): string
{
    return preg_replace('/[^a-z0-9_]/', '_', $name);
}

function loadTemplate(string $template): string
{
    return parse(file_get_contents(__DIR__ . "/templates/$template"));
}
