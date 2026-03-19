<?php

declare(strict_types=1);

$publicDir = dirname(__DIR__).'/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$normalizedPath = is_string($requestPath) && '' !== $requestPath ? $requestPath : '/';
$filePath = realpath($publicDir.$normalizedPath);

if (
    false !== $filePath
    && is_file($filePath)
    && str_starts_with($filePath, realpath($publicDir) ?: $publicDir)
) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeType = match ($extension) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'html' => 'text/html; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        default => mime_content_type($filePath) ?: 'application/octet-stream',
    };

    header('Content-Type: '.$mimeType);
    header('Content-Length: '.(string) filesize($filePath));
    readfile($filePath);
    exit;
}

require $publicDir.'/index.php';
