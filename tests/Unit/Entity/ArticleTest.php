<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    public function testArticleExposesAssignedValues(): void
    {
        $publishedAt = new \DateTimeImmutable('2026-03-16 10:00:00');

        $article = (new Article())
            ->setTitle('Tytul artykulu')
            ->setLanguage(ArticleLanguage::EN)
            ->setSlug('article-title')
            ->setExcerpt('Krotki opis')
            ->setContent('Pelna tresc')
            ->setStatus(ArticleStatus::REVIEW)
            ->setPublishedAt($publishedAt);

        $this->assertSame('Tytul artykulu', $article->getTitle());
        $this->assertSame(ArticleLanguage::EN, $article->getLanguage());
        $this->assertSame('article-title', $article->getSlug());
        $this->assertSame('Krotki opis', $article->getExcerpt());
        $this->assertSame('Pelna tresc', $article->getContent());
        $this->assertSame(ArticleStatus::REVIEW, $article->getStatus());
        $this->assertSame($publishedAt, $article->getPublishedAt());
        $this->assertFalse($article->isPublished());
    }

    public function testIsPublishedReturnsTrueOnlyForPublishedStatus(): void
    {
        $article = (new Article())
            ->setStatus(ArticleStatus::PUBLISHED);

        $this->assertTrue($article->isPublished());

        $article->setStatus(ArticleStatus::ARCHIVED);

        $this->assertFalse($article->isPublished());
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $article = new Article();
        $originalCreatedAt = $article->getCreatedAt();
        $originalUpdatedAt = $article->getUpdatedAt();

        usleep(1000);
        $article->onPrePersist();

        $this->assertGreaterThanOrEqual($originalCreatedAt->getTimestamp(), $article->getCreatedAt()->getTimestamp());
        $this->assertGreaterThanOrEqual($originalUpdatedAt->getTimestamp(), $article->getUpdatedAt()->getTimestamp());

        $updatedAtAfterPersist = $article->getUpdatedAt();

        usleep(1000);
        $article->onPreUpdate();

        $this->assertGreaterThanOrEqual($updatedAtAfterPersist->getTimestamp(), $article->getUpdatedAt()->getTimestamp());
    }
}
