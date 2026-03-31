<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use App\Service\ArticleSlugger;
use App\Service\CategorySlugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CategorySluggerTest extends TestCase
{
    public function testRefreshSlugBuildsUniqueSlugFromPolishTitle(): void
    {
        $category = (new ArticleCategory())
            ->setName('AI')
            ->setTitle('pl', 'Sztuczna inteligencja');

        $slugger = new CategorySlugger(
            $this->createRepositoryMock(['sztuczna-inteligencja']),
            new ArticleSlugger(),
        );

        $slugger->refreshSlug($category);

        $this->assertSame('sztuczna-inteligencja-2', $category->getSlug());
    }

    public function testRefreshSlugFallsBackToTechnicalNameWhenPolishTitleIsMissing(): void
    {
        $category = (new ArticleCategory())
            ->setName('AI Tools');

        $slugger = new CategorySlugger(
            $this->createRepositoryMock([]),
            new ArticleSlugger(),
        );

        $slugger->refreshSlug($category);

        $this->assertSame('ai-tools', $category->getSlug());
    }

    public function testRefreshSlugKeepsFinalValueWithinColumnLimitWhenSuffixIsNeeded(): void
    {
        $baseTitle = str_repeat('a', 255);
        $category = (new ArticleCategory())
            ->setName('Fallback')
            ->setTitle('pl', $baseTitle);

        $slugger = new CategorySlugger(
            $this->createRepositoryMock([str_repeat('a', 255)]),
            new ArticleSlugger(),
        );

        $slugger->refreshSlug($category);

        $this->assertSame(255, strlen($category->getSlug()));
        $this->assertStringEndsWith('-2', $category->getSlug());
    }

    /**
     * @param list<string> $existingSlugs
     */
    private function createRepositoryMock(array $existingSlugs): ArticleCategoryRepository
    {
        /** @var ArticleCategoryRepository&MockObject $repository */
        $repository = $this->createMock(ArticleCategoryRepository::class);
        $repository
            ->method('slugExists')
            ->willReturnCallback(
                static fn (string $slug, ?int $ignoreId = null): bool => in_array($slug, $existingSlugs, true)
            );

        return $repository;
    }
}
