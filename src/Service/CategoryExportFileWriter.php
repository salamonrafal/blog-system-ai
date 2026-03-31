<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleCategory;
use App\Entity\CategoryExportQueue;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CategoryExportFileWriter
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_export_directory%')]
        private readonly string $exportDirectory,
    ) {
    }

    public function write(CategoryExportQueue $queueItem): string
    {
        $absoluteDirectory = $this->projectDir.'/'.$this->exportDirectory;
        $now = $this->utcNow();

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create export directory "%s".', $absoluteDirectory));
        }

        $category = $queueItem->getCategory();
        $slug = $this->requireSlug($category);
        $fileName = sprintf(
            'category-%s-export-%s-%s.json',
            $this->sanitizeSlugForFileName($slug),
            $now->format('Ymd-His'),
            bin2hex(random_bytes(4))
        );
        $relativePath = rtrim($this->exportDirectory, '/').'/'.$fileName;
        $absolutePath = $this->projectDir.'/'.$relativePath;

        $payload = [
            'format' => 'category-export',
            'version' => 1,
            'exported_at' => $now->format(\DateTimeInterface::ATOM),
            'exported_by' => $this->normalizeUser($queueItem->getRequestedBy()),
            'category_count' => 1,
            'category' => [$this->normalizeCategory($category, $queueItem)],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (false === file_put_contents($absolutePath, $json)) {
            throw new \RuntimeException(sprintf('Unable to write export file "%s".', $absolutePath));
        }

        return $relativePath;
    }

    public function delete(string $relativePath): void
    {
        $absolutePath = $this->projectDir.'/'.ltrim($relativePath, '/');

        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to delete export file "%s".', $absolutePath));
        }
    }

    private function normalizeCategory(ArticleCategory $category, CategoryExportQueue $queueItem): array
    {
        return [
            'queue_item_id' => $queueItem->getId(),
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'short_description' => $category->getShortDescription(),
            'titles' => $category->getTitles(),
            'descriptions' => $category->getDescriptions(),
            'icon' => $category->getIcon(),
            'status' => $category->getStatus()->value,
            'created_at' => $category->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $category->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function normalizeUser(?User $user): ?array
    {
        if (null === $user) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'display_name' => $user->getDisplayName(),
        ];
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private function sanitizeSlugForFileName(string $slug): string
    {
        $sanitizedSlug = preg_replace('/[^A-Za-z0-9._-]+/', '-', $slug);
        $sanitizedSlug = trim((string) $sanitizedSlug, '.-_');

        return '' !== $sanitizedSlug ? $sanitizedSlug : 'category';
    }

    private function requireSlug(ArticleCategory $category): string
    {
        $slug = trim($category->getSlug());
        if ('' !== $slug) {
            return $slug;
        }

        throw new \RuntimeException(sprintf(
            'Article category ID %s is missing slug and cannot be exported safely.',
            $category->getId() ?? 'unknown'
        ));
    }
}
