<?php

declare(strict_types=1);

namespace App\Service;

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

    public static function acceptAttribute(): string
    {
        return '.jpg,.jpeg,.png,.webp,.gif,.avif,image/jpeg,image/png,image/webp,image/gif,image/avif';
    }
}
