<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Repository\TopMenuItemRepository;

class TopMenuItemUniqueNameGenerator
{
    public function __construct(
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly SlugBasedUniqueNameGenerator $slugBasedUniqueNameGenerator,
    ) {
    }

    public function refreshUniqueName(TopMenuItem $menuItem): void
    {
        $menuItem->setUniqueName($this->createUniqueName($menuItem));
    }

    private function createUniqueName(TopMenuItem $menuItem): string
    {
        $baseValue = $menuItem->getLabel('pl', null) ?? $menuItem->getLocalizedLabel('pl');

        return $this->slugBasedUniqueNameGenerator->generate(
            $baseValue,
            fn (string $candidate): bool => $this->topMenuItemRepository->uniqueNameExists($candidate, $menuItem->getId()),
            'menu-item',
        );
    }
}
