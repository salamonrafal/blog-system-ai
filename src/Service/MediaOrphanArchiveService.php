<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MediaImageRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MediaOrphanArchiveService
{
    public function __construct(
        private readonly MediaImageRepository $mediaImageRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.media_directory%')]
        private readonly string $mediaDirectory = 'public/uploads/media',
        #[Autowire('%app.media_orphan_ignored_filenames%')]
        private readonly array $ignoredFilenames = ['.gitkeep'],
    ) {
    }

    /**
     * @return array{archive_path: ?string, moved_files: list<string>}
     */
    public function archiveOrphans(): array
    {
        $managedFilePaths = $this->buildManagedFilePathMap();
        $mediaFiles = $this->findMediaFiles();
        $orphanedFiles = array_values(array_filter(
            $mediaFiles,
            static fn (string $path): bool => !isset($managedFilePaths[$path]),
        ));

        if ([] === $orphanedFiles) {
            return [
                'archive_path' => null,
                'moved_files' => [],
            ];
        }

        $stagingDirectory = $this->createStagingDirectory();
        $movedFiles = [];

        try {
            foreach ($orphanedFiles as $relativePath) {
                $targetPath = $stagingDirectory.'/'.$relativePath;
                $this->ensureDirectoryExists(dirname($targetPath));

                $sourcePath = $this->projectDir.'/'.$relativePath;
                if (!@rename($sourcePath, $targetPath)) {
                    throw new \RuntimeException(sprintf('Failed to move orphaned media file "%s" to the staging directory.', $relativePath));
                }

                $movedFiles[$relativePath] = $targetPath;
            }

            $archivePath = $this->createArchiveFromStagingDirectory($stagingDirectory);
            $this->removeDirectory($stagingDirectory);
            $this->removeIfEmpty($this->getArchiveDirectory().'/tmp');
            $this->removeEmptyDirectories($this->projectDir.'/'.trim($this->mediaDirectory, '/'));

            return [
                'archive_path' => $this->toProjectRelativePath($archivePath),
                'moved_files' => $orphanedFiles,
            ];
        } catch (\Throwable $exception) {
            $this->rollbackMovedFiles($movedFiles);
            $this->cleanupStagingDirectory($stagingDirectory);
            $this->removeIfEmpty($this->getArchiveDirectory().'/tmp');

            throw $exception;
        }
    }

    public function getMediaDirectory(): string
    {
        return trim($this->mediaDirectory, '/');
    }

    /**
     * @return array<string, true>
     */
    private function buildManagedFilePathMap(): array
    {
        $paths = [];

        foreach ($this->mediaImageRepository->findAllStoredFilePaths() as $path) {
            $normalized = $this->normalizeRelativePath($path);
            if ('' === $normalized) {
                continue;
            }

            $paths[$normalized] = true;
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function findMediaFiles(): array
    {
        $mediaRoot = $this->projectDir.'/'.trim($this->mediaDirectory, '/');
        if (!is_dir($mediaRoot)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if (in_array($item->getFilename(), $this->ignoredFilenames, true)) {
                continue;
            }

            $absolutePath = str_replace('\\', '/', $item->getPathname());
            $prefix = rtrim(str_replace('\\', '/', $this->projectDir), '/').'/';

            if (!str_starts_with($absolutePath, $prefix)) {
                continue;
            }

            $files[] = substr($absolutePath, strlen($prefix));
        }

        sort($files);

        return $files;
    }

    protected function createStagingDirectory(): string
    {
        $stagingDirectory = $this->getArchiveDirectory().'/tmp/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        $this->ensureDirectoryExists($stagingDirectory);

        return $stagingDirectory;
    }

    protected function createArchiveFromStagingDirectory(string $stagingDirectory): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required to archive orphaned media files.');
        }

        $archiveDirectory = $this->getArchiveDirectory();
        $this->ensureDirectoryExists($archiveDirectory);
        $archivePath = $archiveDirectory.'/media-orphans-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';

        $archive = new \ZipArchive();
        $result = $archive->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if (true !== $result) {
            throw new \RuntimeException(sprintf('Failed to create orphaned media archive "%s".', $this->toProjectRelativePath($archivePath)));
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($stagingDirectory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $absolutePath = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($absolutePath, strlen(rtrim(str_replace('\\', '/', $stagingDirectory), '/'))), '/');

            if ('' === $relativePath) {
                continue;
            }

            if (!$archive->addFile($item->getPathname(), $relativePath)) {
                $archive->close();
                if (is_file($archivePath)) {
                    @unlink($archivePath);
                }

                throw new \RuntimeException(sprintf(
                    'Failed to add orphaned media file "%s" to archive "%s".',
                    $relativePath,
                    $this->toProjectRelativePath($archivePath),
                ));
            }
        }

        $archive->close();

        return $archivePath;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create directory "%s".', $this->toProjectRelativePath($directory)));
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            if (!@unlink($path)) {
                throw new \RuntimeException(sprintf('Failed to remove temporary file "%s".', $this->toProjectRelativePath($path)));
            }
        }

        if (!@rmdir($directory)) {
            throw new \RuntimeException(sprintf('Failed to remove temporary directory "%s".', $this->toProjectRelativePath($directory)));
        }
    }

    private function removeEmptyDirectories(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }

        $entries = scandir($directory);
        if (false === $entries) {
            return false;
        }

        $isEmpty = true;

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                if (!$this->removeEmptyDirectories($path)) {
                    $isEmpty = false;
                }

                continue;
            }

            $isEmpty = false;
        }

        $mediaRoot = rtrim(str_replace('\\', '/', $this->projectDir.'/'.trim($this->mediaDirectory, '/')), '/');
        $normalizedDirectory = rtrim(str_replace('\\', '/', $directory), '/');

        if ($isEmpty && $normalizedDirectory !== $mediaRoot) {
            @rmdir($directory);

            return true;
        }

        return $isEmpty;
    }

    private function removeIfEmpty(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if (false === $entries) {
            return;
        }

        if (['.', '..'] === array_values($entries)) {
            @rmdir($directory);
        }
    }

    /**
     * @param array<string, string> $movedFiles
     */
    private function rollbackMovedFiles(array $movedFiles): void
    {
        foreach (array_reverse($movedFiles, true) as $relativePath => $stagedPath) {
            if (!is_file($stagedPath)) {
                continue;
            }

            $originalPath = $this->projectDir.'/'.$relativePath;
            $this->ensureDirectoryExists(dirname($originalPath));

            if (!@rename($stagedPath, $originalPath)) {
                throw new \RuntimeException(sprintf('Failed to restore orphaned media file "%s" after archive failure.', $relativePath));
            }
        }
    }

    private function cleanupStagingDirectory(string $stagingDirectory): void
    {
        if (!is_dir($stagingDirectory)) {
            return;
        }

        try {
            $this->removeDirectory($stagingDirectory);
        } catch (\Throwable) {
        }
    }

    private function normalizeRelativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $projectPrefix = rtrim(str_replace('\\', '/', $this->projectDir), '/').'/';

        if (str_starts_with($normalized, $projectPrefix)) {
            $normalized = substr($normalized, strlen($projectPrefix));
        }

        return ltrim($normalized, '/');
    }

    private function toProjectRelativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $projectPrefix = rtrim(str_replace('\\', '/', $this->projectDir), '/').'/';

        if (str_starts_with($normalized, $projectPrefix)) {
            return substr($normalized, strlen($projectPrefix));
        }

        return ltrim($normalized, '/');
    }

    private function getArchiveDirectory(): string
    {
        return rtrim(str_replace('\\', '/', $this->projectDir), '/').'/var/media-orphans';
    }
}
