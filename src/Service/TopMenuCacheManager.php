<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TopMenuItemRepository;
use App\Twig\AppGlobalsExtension;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TopMenuCacheManager
{
    public function __construct(
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly TopMenuBuilder $topMenuBuilder,
        private readonly CacheInterface $appCache,
    ) {
    }

    public function refresh(): void
    {
        $items = $this->topMenuItemRepository->findActiveOrdered();

        foreach (AppGlobalsExtension::topMenuCacheKeys() as $cacheKey) {
            $language = substr($cacheKey, strrpos($cacheKey, '.') + 1);
            $this->appCache->delete($cacheKey);
            $this->appCache->get($cacheKey, function (ItemInterface $item) use ($items, $language): array {
                $item->expiresAfter(3600);

                return $this->topMenuBuilder->buildActiveTreeForLanguage($items, $language);
            });
        }
    }
}
