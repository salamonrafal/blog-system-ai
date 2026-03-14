<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV'])) {
    if (class_exists(Dotenv::class)) {
        (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/.env');
    } else {
        $_SERVER['APP_ENV'] = 'dev';
        $_SERVER['APP_DEBUG'] = '1';
    }
}

$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? '1';
$_SERVER['APP_ENV'] ??= $_ENV['APP_ENV'] ?? 'dev';
