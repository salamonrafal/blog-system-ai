<?php

declare(strict_types=1);

namespace App\Service;

class ManagedFileDeleter
{
    public function delete(?string $absolutePath, string $fileLabel = 'managed'): void
    {
        if (null === $absolutePath || !is_file($absolutePath)) {
            return;
        }

        if (!unlink($absolutePath)) {
            throw new \RuntimeException(sprintf('Failed to delete %s file: %s', $fileLabel, $absolutePath));
        }
    }
}
