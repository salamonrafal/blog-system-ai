<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AvatarImageStorage
{
    public function __construct(
        #[Autowire('%app.avatar_directory%')]
        private readonly string $avatarDirectory,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly ?ManagedUploadedFileStorage $managedUploadedFileStorage = null,
        private readonly ?AvatarImageOptimizer $avatarImageOptimizer = null,
        private readonly ?ManagedFileDeleter $managedFileDeleter = null,
    ) {
    }

    /**
     * @return array{relative_path: string, public_path: string, original_filename: string, file_size: int, mime_type: string}
     */
    public function store(UploadedFile $uploadedFile, ?string $previousAvatarPath = null): array
    {
        $detectedMimeType = MediaImageSupport::detectMimeType($uploadedFile);
        $subdirectory = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y/m/d');
        $managedUploadedFileStorage = $this->managedUploadedFileStorage ?? new ManagedUploadedFileStorage($this->projectDir);
        $storedFile = $managedUploadedFileStorage->store(
            $uploadedFile,
            trim($this->avatarDirectory, '/').'/'.$subdirectory,
            'avatar',
            'avatar',
            'jpg',
            MediaImageSupport::preferredExtensionForMimeType($detectedMimeType),
        );

        $absolutePath = $this->projectDir.'/'.$storedFile['relative_path'];
        ($this->avatarImageOptimizer ?? new AvatarImageOptimizer())->optimize($absolutePath, $detectedMimeType);
        $storedMimeType = is_file($absolutePath) ? MediaImageSupport::detectMimeType(new File($absolutePath)) : $detectedMimeType;

        $publicPath = $this->toPublicPath($storedFile['relative_path']);
        $this->deleteReplacedAvatar($previousAvatarPath, $publicPath);

        return $storedFile + [
            'public_path' => $publicPath,
            'file_size' => is_file($absolutePath) ? (filesize($absolutePath) ?: 0) : 0,
            'mime_type' => '' !== $storedMimeType ? $storedMimeType : $detectedMimeType,
        ];
    }

    public function deleteIfManaged(?string $avatarPath): void
    {
        $absoluteAvatarPath = $this->resolveManagedAvatarAbsolutePath($avatarPath);
        if (null === $absoluteAvatarPath) {
            return;
        }

        try {
            ($this->managedFileDeleter ?? new ManagedFileDeleter())->delete($absoluteAvatarPath, 'avatar');
        } catch (\RuntimeException) {
            // Best-effort cleanup: avatar deletion must not break user lifecycle actions.
        }
    }

    private function deleteReplacedAvatar(?string $previousAvatarPath, string $newPublicPath): void
    {
        $absolutePreviousPath = $this->resolveManagedAvatarAbsolutePath($previousAvatarPath);
        if (null === $absolutePreviousPath) {
            return;
        }

        if ($this->toPublicPath($this->toProjectRelativePath($absolutePreviousPath)) === $newPublicPath) {
            return;
        }

        $this->deleteIfManaged($previousAvatarPath);
    }

    private function resolveManagedAvatarAbsolutePath(?string $avatarPath): ?string
    {
        $normalizedAvatarPath = trim((string) $avatarPath);
        if ('' === $normalizedAvatarPath) {
            return null;
        }

        $normalizedAvatarDirectory = trim($this->avatarDirectory, '/');
        $managedPublicPrefix = $this->toPublicPath($normalizedAvatarDirectory).'/';
        if (!str_starts_with($normalizedAvatarPath, $managedPublicPrefix)) {
            return null;
        }

        $relativeSuffix = ltrim(substr($normalizedAvatarPath, strlen($managedPublicPrefix)), '/');
        $relativePath = '' !== $relativeSuffix ? $normalizedAvatarDirectory.'/'.$relativeSuffix : $normalizedAvatarDirectory;
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $realProjectDir = realpath($this->projectDir);
        $realManagedDirectory = realpath($this->projectDir.'/'.$normalizedAvatarDirectory);
        $realAbsolutePath = realpath($absolutePath);

        if (false === $realProjectDir || false === $realManagedDirectory || false === $realAbsolutePath) {
            return null;
        }

        $normalizedProjectDir = rtrim($realProjectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $normalizedManagedDirectory = rtrim($realManagedDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!str_starts_with($realAbsolutePath, $normalizedProjectDir) || !str_starts_with($realAbsolutePath, $normalizedManagedDirectory)) {
            return null;
        }

        return $realAbsolutePath;
    }

    private function toPublicPath(string $relativePath): string
    {
        $normalized = ltrim(trim($relativePath), '/');

        if (str_starts_with($normalized, 'public/')) {
            return '/'.ltrim(substr($normalized, strlen('public/')), '/');
        }

        return '/'.$normalized;
    }

    private function toProjectRelativePath(string $absolutePath): string
    {
        $normalizedAbsolutePath = str_replace('\\', '/', $absolutePath);
        $normalizedProjectDir = rtrim(str_replace('\\', '/', $this->projectDir), '/').'/';

        if (str_starts_with($normalizedAbsolutePath, $normalizedProjectDir)) {
            return substr($normalizedAbsolutePath, strlen($normalizedProjectDir));
        }

        return ltrim($normalizedAbsolutePath, '/');
    }
}
