#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
$pidFile = $projectDir.'/var/dev-server.pid';
$logFile = $projectDir.'/var/log/dev-server.log';
$host = '0.0.0.0';
$port = '8888';
$documentRoot = $projectDir.'/public';
$router = $projectDir.'/bin/dev-router.php';
$command = $argv[1] ?? 'status';

if (!is_dir($projectDir.'/var/log')) {
    mkdir($projectDir.'/var/log', 0777, true);
}

switch ($command) {
    case 'start':
        startServer($pidFile, $logFile, $host, $port, $documentRoot, $router);
        break;

    case 'stop':
        stopServer($pidFile);
        break;

    case 'restart':
        stopServer($pidFile, false);
        startServer($pidFile, $logFile, $host, $port, $documentRoot, $router);
        break;

    case 'status':
        showStatus($pidFile, $host, $port);
        break;

    default:
        fwrite(STDERR, "Usage: php bin/dev-server.php [start|stop|restart|status]\n");
        exit(1);
}

function startServer(
    string $pidFile,
    string $logFile,
    string $host,
    string $port,
    string $documentRoot,
    string $router,
): void {
    $existingPid = readPid($pidFile);

    if (null !== $existingPid && isProcessRunning($existingPid)) {
        fwrite(STDOUT, sprintf("Dev server is already running at http://%s:%s (PID %d)\n", $host, $port, $existingPid));
        return;
    }

    $phpBinary = escapeshellarg(PHP_BINARY);
    $docRoot = escapeshellarg($documentRoot);
    $routerFile = escapeshellarg($router);
    $log = escapeshellarg($logFile);

    if (DIRECTORY_SEPARATOR === '\\') {
        $windowsPhpBinary = escapeshellarg(PHP_BINARY);
        $windowsDocumentRoot = addslashes($documentRoot);
        $windowsRouter = addslashes($router);
        $windowsLogFile = addslashes($logFile);

        $command = sprintf(
            "powershell -NoProfile -Command \"`$process = Start-Process -FilePath %s -ArgumentList '-S','%s:%s','-t','%s','%s' -WorkingDirectory '%s' -RedirectStandardOutput '%s' -RedirectStandardError '%s' -PassThru; `$process.Id\"",
            $windowsPhpBinary,
            $host,
            $port,
            $windowsDocumentRoot,
            $windowsRouter,
            $windowsDocumentRoot,
            $windowsLogFile,
            $windowsLogFile
        );
    } else {
        $command = sprintf(
            'nohup %s -S %s:%s -t %s %s >> %s 2>&1 & echo $!',
            $phpBinary,
            $host,
            $port,
            $docRoot,
            $routerFile,
            $log
        );
    }

    $pidOutput = shell_exec($command);
    $pid = is_string($pidOutput) ? (int) trim($pidOutput) : 0;

    if ($pid <= 0) {
        fwrite(STDERR, "Could not start the dev server.\n");
        exit(1);
    }

    file_put_contents($pidFile, (string) $pid);
    fwrite(STDOUT, sprintf("Dev server started at http://%s:%s (PID %d)\n", $host, $port, $pid));
}

function stopServer(string $pidFile, bool $strict = true): void
{
    $pid = readPid($pidFile);

    if (null === $pid) {
        if ($strict) {
            fwrite(STDOUT, "Dev server is not running.\n");
        }
        return;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        shell_exec(sprintf('taskkill /PID %d /F', $pid));
    } else {
        shell_exec(sprintf('kill %d', $pid));
    }

    if (file_exists($pidFile)) {
        unlink($pidFile);
    }

    fwrite(STDOUT, sprintf("Dev server stopped (PID %d)\n", $pid));
}

function showStatus(string $pidFile, string $host, string $port): void
{
    $pid = readPid($pidFile);

    if (null !== $pid && isProcessRunning($pid)) {
        fwrite(STDOUT, sprintf("Dev server is running at http://%s:%s (PID %d)\n", $host, $port, $pid));
        return;
    }

    fwrite(STDOUT, "Dev server is not running.\n");
}

function readPid(string $pidFile): ?int
{
    if (!file_exists($pidFile)) {
        return null;
    }

    $pid = (int) trim((string) file_get_contents($pidFile));

    return $pid > 0 ? $pid : null;
}

function isProcessRunning(int $pid): bool
{
    if (DIRECTORY_SEPARATOR === '\\') {
        $output = shell_exec(sprintf('tasklist /FI "PID eq %d" /NH', $pid));

        return is_string($output) && str_contains($output, (string) $pid);
    }

    if (function_exists('posix_kill')) {
        return @posix_kill($pid, 0);
    }

    $output = shell_exec(sprintf('ps -p %d', $pid));

    return is_string($output) && str_contains($output, (string) $pid);
}
