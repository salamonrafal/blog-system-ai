<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleKeyword;
use App\Entity\ArticleKeywordExportQueue;
use App\Entity\User;
use App\Repository\ArticleKeywordRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticleKeywordExportFileWriter
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly ArticleKeywordRepository $articleKeywordRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_export_directory%')]
        private readonly string $exportDirectory,
    ) {
    }

    /**
     * @return array{file_path: string, items_count: int}
     */
    public function write(ArticleKeywordExportQueue $queueItem): array
    {
        $absoluteDirectory = $this->projectDir.'/'.$this->exportDirectory;
        $now = $this->utcNow();

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create export directory "%s".', $absoluteDirectory));
        }

        $fileName = sprintf(
            'article-keywords-export-%s-%s.json',
            $now->format('Ymd-His'),
            bin2hex(random_bytes(4))
        );
        $relativePath = rtrim($this->exportDirectory, '/').'/'.$fileName;
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $keywords = $this->articleKeywordRepository->findForExport();

        $payload = [
            'format' => 'article-keyword-export',
            'version' => 1,
            'exported_at' => $now->format(\DateTimeInterface::ATOM),
            'exported_by' => $this->normalizeUser($queueItem->getRequestedBy()),
            'keyword_count' => count($keywords),
            'keywords' => array_map(
                fn (ArticleKeyword $keyword): array => $this->normalizeKeyword($keyword, $queueItem),
                $keywords,
            ),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (false === file_put_contents($absolutePath, $json)) {
            throw new \RuntimeException(sprintf('Unable to write export file "%s".', $absolutePath));
        }

        return [
            'file_path' => $relativePath,
            'items_count' => count($keywords),
        ];
    }

    public function delete(string $relativePath): void
    {
        $absolutePath = $this->projectDir.'/'.ltrim($relativePath, '/');

        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to delete export file "%s".', $absolutePath));
        }
    }

    private function normalizeKeyword(ArticleKeyword $keyword, ArticleKeywordExportQueue $queueItem): array
    {
        return [
            'queue_item_id' => $queueItem->getId(),
            'id' => $keyword->getId(),
            'name' => $keyword->getName(),
            'language' => $keyword->getLanguage()->value,
            'status' => $keyword->getStatus()->value,
            'color' => $keyword->getColor(),
            'article_ids' => array_values(array_filter(array_map(
                static fn (object $article): ?int => method_exists($article, 'getId') ? $article->getId() : null,
                $keyword->getArticles()->toArray(),
            ), static fn (?int $articleId): bool => null !== $articleId)),
            'created_at' => $keyword->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $keyword->getUpdatedAt()->format(\DateTimeInterface::ATOM),
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
}
