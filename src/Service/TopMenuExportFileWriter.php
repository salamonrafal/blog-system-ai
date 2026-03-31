<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Entity\TopMenuExportQueue;
use App\Entity\User;
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

    public function write(TopMenuExportQueue $queueItem): string
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

        return $relativePath;
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
        return [
            'queue_item_id' => $queueItem->getId(),
            'id' => $item->getId(),
            'parent_id' => $item->getParent()?->getId(),
            'labels' => $item->getLabels(),
            'target_type' => $item->getTargetType()->value,
            'external_url' => $item->getExternalUrl(),
            'external_url_open_in_new_window' => $item->isExternalUrlOpenInNewWindow(),
            'article_category_id' => $item->getArticleCategory()?->getId(),
            'article_id' => $item->getArticle()?->getId(),
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
}
