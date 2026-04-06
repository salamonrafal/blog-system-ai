<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

final class MediaImageSupport
{
    /** @var array<string, list<string>> */
    private const MIME_TYPE_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
        'image/avif' => ['avif'],
    ];

    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    public static function supportsFilename(string $filename): bool
    {
        $extension = self::normalizeExtension(pathinfo($filename, PATHINFO_EXTENSION));

        return \in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    public static function supportsMimeType(?string $mimeType): bool
    {
        return \in_array(self::normalizeMimeType($mimeType), self::ALLOWED_MIME_TYPES, true);
    }

    public static function detectMimeType(File $file): string
    {
        $pathname = $file->getPathname();
        if ('' !== $pathname && is_file($pathname) && class_exists(\finfo::class)) {
            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $detectedMimeType = $finfo->file($pathname);
            if (is_string($detectedMimeType) && '' !== trim($detectedMimeType)) {
                return self::normalizeMimeType($detectedMimeType);
            }
        }

        try {
            return self::normalizeMimeType($file->getMimeType());
        } catch (\LogicException) {
            return '';
        }
    }

    public static function filenameMatchesMimeType(string $filename, ?string $mimeType): bool
    {
        $extension = self::normalizeExtension(pathinfo($filename, PATHINFO_EXTENSION));
        $supportedExtensions = self::extensionsForMimeType($mimeType);

        return '' !== $extension && [] !== $supportedExtensions && \in_array($extension, $supportedExtensions, true);
    }

    public static function preferredExtensionForMimeType(?string $mimeType): ?string
    {
        $supportedExtensions = self::extensionsForMimeType($mimeType);

        return [] !== $supportedExtensions ? $supportedExtensions[0] : null;
    }

    public static function acceptAttribute(): string
    {
        return '.jpg,.jpeg,.png,.webp,.gif,.avif,image/jpeg,image/png,image/webp,image/gif,image/avif';
    }

    private static function normalizeMimeType(?string $mimeType): string
    {
        return strtolower(trim((string) $mimeType));
    }

    private static function normalizeExtension(string $extension): string
    {
        return strtolower(trim($extension, ". \t\n\r\0\x0B"));
    }

    /**
     * @return list<string>
     */
    private static function extensionsForMimeType(?string $mimeType): array
    {
        return self::MIME_TYPE_EXTENSIONS[self::normalizeMimeType($mimeType)] ?? [];
    }
}
