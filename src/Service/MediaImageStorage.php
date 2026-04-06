<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaImageStorage
{
    public function __construct(
        #[Autowire('%app.media_directory%')]
        private readonly string $mediaDirectory,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        ?ManagedUploadedFileStorage $managedUploadedFileStorage = null,
    ) {
        $this->managedUploadedFileStorage = $managedUploadedFileStorage ?? new ManagedUploadedFileStorage($this->projectDir);
    }

    private readonly ManagedUploadedFileStorage $managedUploadedFileStorage;

    /**
     * @return array{relative_path: string, original_filename: string, file_size: int, mime_type: string}
     */
    public function store(UploadedFile $uploadedFile, string $filenamePrefix = 'media-image'): array
    {
        $subdirectory = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y/m/d');
        $detectedMimeType = MediaImageSupport::detectMimeType($uploadedFile);
        $storedFile = $this->managedUploadedFileStorage->store(
            $uploadedFile,
            trim($this->mediaDirectory, '/').'/'.$subdirectory,
            $filenamePrefix,
            'image',
            'jpg',
            MediaImageSupport::preferredExtensionForMimeType($detectedMimeType),
        );

        $absolutePath = $this->projectDir.'/'.$storedFile['relative_path'];
        $storedMimeType = is_file($absolutePath) ? MediaImageSupport::detectMimeType(new \Symfony\Component\HttpFoundation\File\File($absolutePath)) : '';

        return $storedFile + [
            'file_size' => is_file($absolutePath) ? (filesize($absolutePath) ?: 0) : 0,
            'mime_type' => '' !== $storedMimeType ? $storedMimeType : $detectedMimeType,
        ];
    }
}
