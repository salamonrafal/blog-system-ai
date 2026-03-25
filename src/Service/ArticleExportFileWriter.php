<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleExportQueue;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticleExportFileWriter
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_export_directory%')]
        private readonly string $exportDirectory,
    ) {
    }

    public function write(ArticleExportQueue $queueItem): string
    {
        $absoluteDirectory = $this->projectDir.'/'.$this->exportDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create export directory "%s".', $absoluteDirectory));
        }

        $article = $queueItem->getArticle();
        $fileName = sprintf(
            'article-%s-export-%s-%s.json',
            $this->sanitizeSlugForFileName($article->getSlug()),
            $this->utcNow()->format('Ymd-His'),
            bin2hex(random_bytes(4))
        );
        $relativePath = rtrim($this->exportDirectory, '/').'/'.$fileName;
        $absolutePath = $this->projectDir.'/'.$relativePath;

        $payload = [
            'format' => 'article-export',
            'version' => 1,
            'exported_at' => $this->utcNow()->format(\DateTimeInterface::ATOM),
            'article_count' => 1,
            'article' => [$this->normalizeArticle($article, $queueItem)],
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

        if (!is_file($absolutePath)) {
            return;
        }

        if (!@unlink($absolutePath) && is_file($absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to delete export file "%s".', $absolutePath));
        }
    }

    private function normalizeArticle(Article $article, ArticleExportQueue $queueItem): array
    {
        return [
            'queue_item_id' => $queueItem->getId(),
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'language' => $article->getLanguage()->value,
            'slug' => $article->getSlug(),
            'excerpt' => $article->getExcerpt(),
            'headline_image' => $article->getHeadlineImage(),
            'headline_image_enabled' => $article->isHeadlineImageEnabled(),
            'content' => $article->getContent(),
            'status' => $article->getStatus()->value,
            'published_at' => $article->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $article->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $article->getUpdatedAt()->format(\DateTimeInterface::ATOM),
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

        return '' !== $sanitizedSlug ? $sanitizedSlug : 'article';
    }
}
