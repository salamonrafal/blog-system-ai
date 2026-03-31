<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;

class CategorySlugger
{
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
        $baseSlug = $this->articleSlugger->slugify($baseValue);
        $slug = '' !== $baseSlug ? $baseSlug : 'category';
        $baseSlug = $slug;
        $counter = 2;

        while ($this->articleCategoryRepository->slugExists($slug, $category->getId())) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            ++$counter;
        }

        return $slug;
    }
}
