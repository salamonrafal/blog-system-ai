<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Entity\TopMenuExportQueue;
use App\Entity\User;
use App\Enum\TopMenuItemTargetType;
use App\Repository\TopMenuItemRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TopMenuExportFileWriter
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly TopMenuItemRepository $topMenuItemRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_export_directory%')]
        private readonly string $exportDirectory,
    ) {
    }

    /**
     * @return array{file_path: string, items_count: int}
     */
    public function write(TopMenuExportQueue $queueItem): array
    {
        $absoluteDirectory = $this->projectDir.'/'.$this->exportDirectory;
        $now = $this->utcNow();

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create export directory "%s".', $absoluteDirectory));
        }

        $fileName = sprintf(
            'top-menu-export-%s-%s.json',
            $now->format('Ymd-His'),
            bin2hex(random_bytes(4))
        );
        $relativePath = rtrim($this->exportDirectory, '/').'/'.$fileName;
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $items = $this->topMenuItemRepository->findForAdminIndex();

        $payload = [
            'format' => 'top-menu-export',
            'version' => 1,
            'exported_at' => $now->format(\DateTimeInterface::ATOM),
            'exported_by' => $this->normalizeUser($queueItem->getRequestedBy()),
            'menu_item_count' => count($items),
            'menu_items' => array_map(
                fn (TopMenuItem $item): array => $this->normalizeMenuItem($item, $queueItem),
                $items,
            ),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (false === file_put_contents($absolutePath, $json)) {
            throw new \RuntimeException(sprintf('Unable to write export file "%s".', $absolutePath));
        }

        return [
            'file_path' => $relativePath,
            'items_count' => count($items),
        ];
    }

    public function delete(string $relativePath): void
    {
        $absolutePath = $this->projectDir.'/'.ltrim($relativePath, '/');

        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to delete export file "%s".', $absolutePath));
        }
    }

    private function normalizeMenuItem(TopMenuItem $item, TopMenuExportQueue $queueItem): array
    {
        $parent = $item->getParent();
        $uniqueName = $this->requireUniqueName($item);
        $parentUniqueName = null !== $parent ? $this->requireUniqueName($parent) : null;

        $targetType = $item->getTargetType();
        $articleCategory = TopMenuItemTargetType::ARTICLE_CATEGORY === $targetType ? $item->getArticleCategory() : null;
        $article = TopMenuItemTargetType::ARTICLE === $targetType ? $item->getArticle() : null;
        $externalUrl = TopMenuItemTargetType::EXTERNAL_URL === $targetType ? $item->getExternalUrl() : null;
        $externalUrlOpenInNewWindow = TopMenuItemTargetType::EXTERNAL_URL === $targetType ? $item->isExternalUrlOpenInNewWindow() : false;

        return [
            'queue_item_id' => $queueItem->getId(),
            'id' => $item->getId(),
            'unique_name' => $uniqueName,
            'parent_id' => $parent?->getId(),
            'parent_unique_name' => $parentUniqueName,
            'labels' => $item->getLabels(),
            'target_type' => $targetType->value,
            'external_url' => $externalUrl,
            'external_url_open_in_new_window' => $externalUrlOpenInNewWindow,
            'article_category_id' => $articleCategory?->getId(),
            'category_slug' => $articleCategory?->getSlug(),
            'article_id' => $article?->getId(),
            'article_slug' => $article?->getSlug(),
            'position' => $item->getPosition(),
            'status' => $item->getStatus()->value,
            'created_at' => $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $item->getUpdatedAt()->format(\DateTimeInterface::ATOM),
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

    private function requireUniqueName(TopMenuItem $item): string
    {
        $uniqueName = trim($item->getUniqueName());
        if ('' !== $uniqueName) {
            return $uniqueName;
        }

        throw new \RuntimeException(sprintf(
            'Top menu item ID %s is missing unique_name and cannot be exported safely.',
            $item->getId() ?? 'unknown'
        ));
    }
}
