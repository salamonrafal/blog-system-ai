<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MediaImage;

class MediaGalleryManager
{
    public function __construct(
        private readonly ManagedFileDeleter $managedFileDeleter,
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    public function delete(MediaImage $mediaImage): void
    {
        $absolutePath = $this->managedFilePathResolver->resolveMediaPath($mediaImage->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'media');
    }

    /**
     * @param iterable<MediaImage> $mediaImages
     */
    public function clear(iterable $mediaImages): void
    {
        foreach ($mediaImages as $mediaImage) {
            $this->delete($mediaImage);
        }
    }
}
