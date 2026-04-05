<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

final class MediaImageSupport
{
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    public static function supportsFilename(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return \in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    public static function supportsMimeType(?string $mimeType): bool
    {
        return \in_array(self::normalizeMimeType($mimeType), self::ALLOWED_MIME_TYPES, true);
    }

    public static function detectMimeType(File $file): string
    {
        $pathname = $file->getPathname();
        if ('' !== $pathname && is_file($pathname)) {
            $detectedMimeType = mime_content_type($pathname);
            if (is_string($detectedMimeType) && '' !== trim($detectedMimeType)) {
                return self::normalizeMimeType($detectedMimeType);
            }
        }

        return self::normalizeMimeType($file->getMimeType());
    }

    public static function acceptAttribute(): string
    {
        return '.jpg,.jpeg,.png,.webp,.gif,.avif,image/jpeg,image/png,image/webp,image/gif,image/avif';
    }

    private static function normalizeMimeType(?string $mimeType): string
    {
        return strtolower(trim((string) $mimeType));
    }
}
