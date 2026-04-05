<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ManagedUploadedFileStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{relative_path: string, original_filename: string}
     */
    public function store(
        UploadedFile $uploadedFile,
        string $targetDirectory,
        string $filenamePrefix,
        string $defaultFilename,
        string $defaultExtension,
    ): array {
        $normalizedTargetDirectory = trim($targetDirectory, '/');
        $absoluteTargetDirectory = $this->projectDir.'/'.$normalizedTargetDirectory;

        if (!is_dir($absoluteTargetDirectory) && !mkdir($absoluteTargetDirectory, 0775, true) && !is_dir($absoluteTargetDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created.', $absoluteTargetDirectory));
        }

        $originalFilename = $this->normalizeOriginalFilename($uploadedFile->getClientOriginalName());
        $safeOriginalFilename = '' !== $originalFilename ? $originalFilename : $defaultFilename;
        $extension = strtolower(pathinfo($safeOriginalFilename, PATHINFO_EXTENSION));

        if ('' === $extension) {
            $extension = $defaultExtension;
        }

        $storedFilename = sprintf(
            '%s-%s-%s.%s',
            trim($filenamePrefix),
            (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('YmdHis'),
            bin2hex(random_bytes(6)),
            $extension
        );

        $uploadedFile->move($absoluteTargetDirectory, $storedFilename);

        return [
            'relative_path' => $normalizedTargetDirectory.'/'.$storedFilename,
            'original_filename' => $safeOriginalFilename,
        ];
    }

    private function normalizeOriginalFilename(string $originalFilename): string
    {
        $trimmedFilename = trim($originalFilename);
        if ('' === $trimmedFilename) {
            return '';
        }

        $normalizedSeparators = str_replace('\\', '/', $trimmedFilename);

        return basename($normalizedSeparators);
    }
}
