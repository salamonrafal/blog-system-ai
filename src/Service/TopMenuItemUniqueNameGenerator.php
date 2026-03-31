<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Repository\TopMenuItemRepository;

class TopMenuItemUniqueNameGenerator
{
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
        $baseUniqueName = $this->articleSlugger->slugify($baseValue);
        $uniqueName = '' !== $baseUniqueName ? $baseUniqueName : 'menu-item';
        $baseUniqueName = $uniqueName;
        $counter = 2;

        while ($this->topMenuItemRepository->uniqueNameExists($uniqueName, $menuItem->getId())) {
            $uniqueName = sprintf('%s-%d', $baseUniqueName, $counter);
            ++$counter;
        }

        return $uniqueName;
    }
}
