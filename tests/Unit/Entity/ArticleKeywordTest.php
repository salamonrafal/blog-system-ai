<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Entity\ArticleKeyword;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use PHPUnit\Framework\TestCase;

final class ArticleKeywordTest extends TestCase
{
    public function testKeywordExposesAssignedValues(): void
    {
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article');

        $keyword = (new ArticleKeyword())
            ->setName('php-8-4')
            ->setLanguage(ArticleKeywordLanguage::EN)
            ->setStatus(ArticleCategoryStatus::INACTIVE)
            ->setColor('#123abc')
            ->addArticle($article);

        $this->assertSame('php-8-4', $keyword->getName());
        $this->assertSame(ArticleKeywordLanguage::EN, $keyword->getLanguage());
        $this->assertSame(ArticleCategoryStatus::INACTIVE, $keyword->getStatus());
        $this->assertSame('#123abc', $keyword->getColor());
        $this->assertFalse($keyword->isActive());
        $this->assertCount(1, $keyword->getArticles());
        $this->assertSame($article, $keyword->getArticles()->first());
    }

    public function testKeywordAllowsMissingColor(): void
    {
        $keyword = (new ArticleKeyword())
            ->setName('php')
            ->setColor(null);

        $this->assertNull($keyword->getColor());
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $keyword = (new ArticleKeyword())
            ->setName('php');

        $originalCreatedAt = $keyword->getCreatedAt();
        $originalUpdatedAt = $keyword->getUpdatedAt();

        usleep(1000);
        $keyword->onPrePersist();

        $this->assertGreaterThanOrEqual(
            (float) $originalCreatedAt->format('U.u'),
            (float) $keyword->getCreatedAt()->format('U.u'),
        );
        $this->assertGreaterThan(
            (float) $originalUpdatedAt->format('U.u'),
            (float) $keyword->getUpdatedAt()->format('U.u'),
        );

        $updatedAtAfterPersist = $keyword->getUpdatedAt();

        usleep(1000);
        $keyword->onPreUpdate();

        $this->assertGreaterThan(
            (float) $updatedAtAfterPersist->format('U.u'),
            (float) $keyword->getUpdatedAt()->format('U.u'),
        );
    }

    public function testLifecycleCallbacksRejectPersistingKeywordWithoutName(): void
    {
        $keyword = new ArticleKeyword();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Article keyword name cannot be empty when persisting.');

        $keyword->onPrePersist();
    }
}
