<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;

class CategorySlugger
{
    private const MAX_SLUG_LENGTH = 255;

    public function __construct(
        private readonly ArticleCategoryRepository $articleCategoryRepository,
        private readonly ArticleSlugger $articleSlugger,
    ) {
    }

    public function refreshSlug(ArticleCategory $category): void
    {
        $category->setSlug($this->createUniqueSlug($category));
    }

    private function createUniqueSlug(ArticleCategory $category): string
    {
        $baseValue = $category->getTitle('pl', null) ?? $category->getName();
        $baseSlug = $this->truncateSlug($this->articleSlugger->slugify($baseValue));
        $slug = '' !== $baseSlug ? $baseSlug : 'category';
        $baseSlug = $slug;
        $counter = 2;

        while ($this->articleCategoryRepository->slugExists($slug, $category->getId())) {
            $suffix = sprintf('-%d', $counter);
            $slug = $this->truncateSlug($baseSlug, strlen($suffix)).$suffix;
            ++$counter;
        }

        return $slug;
    }

    private function truncateSlug(string $slug, int $reservedSuffixLength = 0): string
    {
        $maxBaseLength = max(1, self::MAX_SLUG_LENGTH - $reservedSuffixLength);

        return rtrim(substr($slug, 0, $maxBaseLength), '-');
    }
}
