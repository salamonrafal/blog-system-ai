<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Repository\TopMenuItemRepository;

class TopMenuItemUniqueNameGenerator
{
    private const MAX_UNIQUE_NAME_LENGTH = 255;

    public function __construct(
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly ArticleSlugger $articleSlugger,
    ) {
    }

    public function refreshUniqueName(TopMenuItem $menuItem): void
    {
        $menuItem->setUniqueName($this->createUniqueName($menuItem));
    }

    private function createUniqueName(TopMenuItem $menuItem): string
    {
        $baseValue = $menuItem->getLabel('pl', null) ?? $menuItem->getLocalizedLabel('pl');
        $baseUniqueName = $this->truncateUniqueName($this->articleSlugger->slugify($baseValue));
        $uniqueName = '' !== $baseUniqueName ? $baseUniqueName : 'menu-item';
        $baseUniqueName = $uniqueName;
        $counter = 2;

        while ($this->topMenuItemRepository->uniqueNameExists($uniqueName, $menuItem->getId())) {
            $suffix = sprintf('-%d', $counter);
            $uniqueName = $this->truncateUniqueName($baseUniqueName, strlen($suffix)).$suffix;
            ++$counter;
        }

        return $uniqueName;
    }

    private function truncateUniqueName(string $uniqueName, int $reservedSuffixLength = 0): string
    {
        $maxBaseLength = max(1, self::MAX_UNIQUE_NAME_LENGTH - $reservedSuffixLength);

        return rtrim(substr($uniqueName, 0, $maxBaseLength), '-');
    }
}
