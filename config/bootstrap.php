<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);
$runtimeVariables = ['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'DATABASE_URL'];

if (!isset($_SERVER['APP_ENV'])) {
    if (class_exists(Dotenv::class) && is_file($projectDir.'/.env')) {
        (new Dotenv())->usePutenv()->bootEnv($projectDir.'/.env');
    } else {
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV');
        $appDebug = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');

        $appEnv = $appEnv === false ? 'dev' : (string) $appEnv;
        $appDebug = $appDebug === false ? '1' : (string) $appDebug;

        $_SERVER['APP_ENV'] = $appEnv;
        $_ENV['APP_ENV'] = $appEnv;
        $_SERVER['APP_DEBUG'] = $appDebug;
        $_ENV['APP_DEBUG'] = $appDebug;
    }
}

foreach ($runtimeVariables as $variable) {
    $value = $_SERVER[$variable] ?? $_ENV[$variable] ?? getenv($variable);

    if ($value === false || $value === null || $value === '') {
        continue;
    }

    $_SERVER[$variable] = (string) $value;
    $_ENV[$variable] = (string) $value;
}

$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? '1';
$_SERVER['APP_ENV'] ??= $_ENV['APP_ENV'] ?? 'dev';
