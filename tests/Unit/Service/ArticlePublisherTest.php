<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use App\Service\ArticlePublisher;
use App\Service\ArticleSlugger;
use App\Tests\TestCase;

final class ArticlePublisherTest extends TestCase
{
    public function testPrepareForSaveGeneratesUniqueSlugAndPublicationDateForPublishedArticle(): void
    {
        $article = (new Article())
            ->setTitle('Nowy wpis o Symfony')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::PUBLISHED);

        $publisher = new ArticlePublisher(
            new InMemoryArticleRepository(['nowy-wpis-o-symfony', 'nowy-wpis-o-symfony-2']),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('nowy-wpis-o-symfony-3', $article->getSlug());
        $this->assertInstanceOf(\DateTimeImmutable::class, $article->getPublishedAt());
    }

    public function testPrepareForSaveKeepsExistingSlugAndPublicationDate(): void
    {
        $publishedAt = new \DateTimeImmutable('-1 day');
        $article = (new Article())
            ->setTitle('Nowy wpis o Symfony')
            ->setSlug('wlasny-slug')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::PUBLISHED)
            ->setPublishedAt($publishedAt);

        $publisher = new ArticlePublisher(
            new InMemoryArticleRepository(['nowy-wpis-o-symfony']),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('wlasny-slug', $article->getSlug());
        $this->assertSame($publishedAt, $article->getPublishedAt());
    }

    public function testPrepareForSaveClearsPublicationDateForNonPublishedArticle(): void
    {
        $article = (new Article())
            ->setTitle('Szkic')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::DRAFT)
            ->setPublishedAt(new \DateTimeImmutable('-2 hours'));

        $publisher = new ArticlePublisher(
            new InMemoryArticleRepository([]),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('szkic', $article->getSlug());
        $this->assertNull($article->getPublishedAt());
    }
}

final class InMemoryArticleRepository extends ArticleRepository
{
    /**
     * @param list<string> $existingSlugs
     */
    public function __construct(
        private array $existingSlugs,
    ) {
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return in_array($slug, $this->existingSlugs, true);
    }
}
