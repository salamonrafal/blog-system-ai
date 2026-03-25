<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleImportStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_import_directory%')]
        private readonly string $importDirectory,
    ) {
    }

    /**
     * @return array{relative_path: string, original_filename: string}
     */
    public function store(UploadedFile $uploadedFile): array
    {
        $targetDirectory = $this->projectDir.'/'.trim($this->importDirectory, '/');
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException(sprintf('Import directory "%s" could not be created.', $targetDirectory));
        }

        $originalFilename = trim((string) $uploadedFile->getClientOriginalName());
        $safeOriginalFilename = '' !== $originalFilename ? $originalFilename : 'import.json';
        $extension = strtolower(pathinfo($safeOriginalFilename, PATHINFO_EXTENSION));
        if ('' === $extension) {
            $extension = 'json';
        }

        $storedFilename = sprintf(
            'article-import-%s-%s.%s',
            (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('YmdHis'),
            bin2hex(random_bytes(6)),
            $extension
        );

        $uploadedFile->move($targetDirectory, $storedFilename);

        return [
            'relative_path' => trim($this->importDirectory, '/').'/'.$storedFilename,
            'original_filename' => $safeOriginalFilename,
        ];
    }
}
