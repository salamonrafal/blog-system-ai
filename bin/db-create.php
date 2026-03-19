#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
$envFile = $projectDir.'/.env';

if (!file_exists($envFile)) {
    fwrite(STDERR, ".env file not found.\n");
    exit(1);
}

$env = (string) file_get_contents($envFile);
preg_match('/^DATABASE_URL="sqlite:\/\/\/%kernel\.project_dir%(?<path>[^"]+)"$/m', $env, $matches);

if (!isset($matches['path'])) {
    fwrite(STDERR, "DATABASE_URL is not configured for a local SQLite file.\n");
    exit(1);
}

$databasePath = $projectDir.$matches['path'];
$databaseDir = dirname($databasePath);

if (!is_dir($databaseDir) && !mkdir($databaseDir, 0777, true) && !is_dir($databaseDir)) {
    fwrite(STDERR, sprintf("Could not create directory: %s\n", $databaseDir));
    exit(1);
}

if (!file_exists($databasePath) && false === touch($databasePath)) {
    fwrite(STDERR, sprintf("Could not create database file: %s\n", $databasePath));
    exit(1);
}

fwrite(STDOUT, sprintf("SQLite database is ready: %s\n", $databasePath));
