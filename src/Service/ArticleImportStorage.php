<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleImportStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire('%app.article_import_directory%')]
        private readonly string $importDirectory,
        ?ManagedUploadedFileStorage $managedUploadedFileStorage = null,
    ) {
        $this->managedUploadedFileStorage = $managedUploadedFileStorage ?? new ManagedUploadedFileStorage($projectDir);
    }

    private readonly ManagedUploadedFileStorage $managedUploadedFileStorage;

    /**
     * @return array{relative_path: string, original_filename: string}
     */
    public function store(UploadedFile $uploadedFile, string $filenamePrefix = 'article-import'): array
    {
        return $this->managedUploadedFileStorage->store(
            $uploadedFile,
            $this->importDirectory,
            $filenamePrefix,
            'import.json',
            'json',
        );
    }
}
