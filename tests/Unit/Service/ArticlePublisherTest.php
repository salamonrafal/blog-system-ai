<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use App\Service\ArticlePublisher;
use App\Service\ArticleSlugger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ArticlePublisherTest extends TestCase
{
    public function testPrepareForSaveGeneratesUniqueSlugAndPublicationDateForPublishedArticle(): void
    {
        $article = (new Article())
            ->setTitle('Nowy wpis o Symfony')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::PUBLISHED);

        $publisher = new ArticlePublisher(
            $this->createRepositoryMock([
                'nowy-wpis-o-symfony',
                'nowy-wpis-o-symfony-2',
            ]),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('nowy-wpis-o-symfony-3', $article->getSlug());
        $this->assertInstanceOf(\DateTimeImmutable::class, $article->getPublishedAt());
        $this->assertSame('UTC', $article->getPublishedAt()?->getTimezone()->getName());
    }

    public function testPrepareForSaveKeepsExistingSlugAndPublicationDate(): void
    {
        $publishedAt = new \DateTimeImmutable('2026-03-17 08:30:00', new \DateTimeZone('Europe/Warsaw'));
        $article = (new Article())
            ->setTitle('Nowy wpis o Symfony')
            ->setSlug('wlasny-slug')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::PUBLISHED)
            ->setPublishedAt($publishedAt);

        $publisher = new ArticlePublisher(
            $this->createRepositoryMock(['nowy-wpis-o-symfony']),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('wlasny-slug', $article->getSlug());
        $this->assertSame('2026-03-17 07:30:00', $article->getPublishedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $article->getPublishedAt()?->getTimezone()->getName());
    }

    public function testPrepareForSaveClearsPublicationDateForNonPublishedArticle(): void
    {
        $article = (new Article())
            ->setTitle('Szkic')
            ->setContent('Tresc artykulu')
            ->setStatus(ArticleStatus::DRAFT)
            ->setPublishedAt(new \DateTimeImmutable('-2 hours'));

        $publisher = new ArticlePublisher(
            $this->createRepositoryMock([]),
            new ArticleSlugger(),
        );

        $publisher->prepareForSave($article);

        $this->assertSame('szkic', $article->getSlug());
        $this->assertNull($article->getPublishedAt());
    }

    /**
     * @param list<string> $existingSlugs
     */
    private function createRepositoryMock(array $existingSlugs): ArticleRepository
    {
        /** @var ArticleRepository&MockObject $repository */
        $repository = $this->createMock(ArticleRepository::class);
        $repository
            ->method('slugExists')
            ->willReturnCallback(
                static fn (string $slug, ?int $ignoreId = null): bool => in_array($slug, $existingSlugs, true)
            );

        return $repository;
    }
}
