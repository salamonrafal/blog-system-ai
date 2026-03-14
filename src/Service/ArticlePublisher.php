<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;

class ArticlePublisher
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly ArticleSlugger $articleSlugger,
    ) {
    }

    public function prepareForSave(Article $article): void
    {
        if ('' === trim($article->getSlug())) {
            $article->setSlug($this->createUniqueSlug($article));
        }

        if (ArticleStatus::PUBLISHED === $article->getStatus() && null === $article->getPublishedAt()) {
            $article->setPublishedAt(new \DateTimeImmutable());
        }

        if (ArticleStatus::PUBLISHED !== $article->getStatus()) {
            $article->setPublishedAt(null);
        }
    }

    private function createUniqueSlug(Article $article): string
    {
        $baseSlug = $this->articleSlugger->slugify($article->getTitle());
        $slug = $baseSlug;
        $counter = 2;

        while ($this->articleRepository->slugExists($slug, $article->getId())) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            ++$counter;
        }

        return $slug;
    }
}
