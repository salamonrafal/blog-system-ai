<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);
$runtimeVariables = ['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'DATABASE_URL'];
$hasRuntimeConfiguration = false;

foreach ($runtimeVariables as $variable) {
    $value = $_SERVER[$variable] ?? $_ENV[$variable] ?? getenv($variable);

    if ($value === false || $value === null || $value === '') {
        continue;
    }

    $hasRuntimeConfiguration = true;
    $_SERVER[$variable] = (string) $value;
    $_ENV[$variable] = (string) $value;
}

if (!isset($_SERVER['APP_ENV'])) {
    if (!$hasRuntimeConfiguration && class_exists(Dotenv::class) && is_file($projectDir.'/.env')) {
        (new Dotenv())->usePutenv()->bootEnv($projectDir.'/.env');
    } else {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] ?? 'dev';
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? '1';
    }
}

$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? '1';
$_SERVER['APP_ENV'] ??= $_ENV['APP_ENV'] ?? 'dev';
