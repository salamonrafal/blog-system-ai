<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ManagedFilePathResolver
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_export_directory%')]
        private readonly string $exportDirectory,
        #[Autowire('%app.article_import_directory%')]
        private readonly string $importDirectory,
        #[Autowire('%app.media_directory%')]
        private readonly string $mediaDirectory = 'public/uploads/media',
    ) {
    }

    public function resolveExportPath(string $relativePath): ?string
    {
        return $this->resolvePath($relativePath, $this->exportDirectory);
    }

    public function resolveImportPath(string $relativePath): ?string
    {
        return $this->resolvePath($relativePath, $this->importDirectory);
    }

    public function resolveMediaPath(string $relativePath): ?string
    {
        return $this->resolvePath($relativePath, $this->mediaDirectory);
    }

    private function resolvePath(string $relativePath, string $managedDirectory): ?string
    {
        $relativePath = ltrim(trim($relativePath), '/');
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $realProjectDir = realpath($this->projectDir);
        $realManagedDirectory = realpath($this->projectDir.'/'.trim($managedDirectory, '/'));
        $realPath = realpath($absolutePath);

        if (false === $realProjectDir || false === $realManagedDirectory || false === $realPath) {
            return null;
        }

        $normalizedProjectDir = rtrim($realProjectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $normalizedManagedDirectory = rtrim($realManagedDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!str_starts_with($realPath, $normalizedProjectDir) || !str_starts_with($realPath, $normalizedManagedDirectory)) {
            return null;
        }

        return $realPath;
    }
}
