<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ArticleCategory;
use App\Enum\ArticleCategoryStatus;
use PHPUnit\Framework\TestCase;

final class ArticleCategoryTest extends TestCase
{
    public function testCategoryExposesAssignedValues(): void
    {
        $category = (new ArticleCategory())
            ->setName('PHP')
            ->setShortDescription('Backend i architektura aplikacji.')
            ->setTitle('pl', 'Programowanie w PHP')
            ->setTitle('en', 'PHP Development')
            ->setDescription('pl', 'Kategoria dla tresci po polsku.')
            ->setDescription('en', 'Category for English content.')
            ->setIcon('ph ph-code')
            ->setStatus(ArticleCategoryStatus::INACTIVE);

        $this->assertSame('PHP', $category->getName());
        $this->assertSame('Backend i architektura aplikacji.', $category->getShortDescription());
        $this->assertSame('Programowanie w PHP', $category->getTitle('pl'));
        $this->assertSame('PHP Development', $category->getTitle('en'));
        $this->assertSame('Kategoria dla tresci po polsku.', $category->getDescription('pl'));
        $this->assertSame('Category for English content.', $category->getDescription('en'));
        $this->assertSame('PHP Development', $category->getLocalizedTitle('en'));
        $this->assertSame('ph ph-code', $category->getIcon());
        $this->assertSame(ArticleCategoryStatus::INACTIVE, $category->getStatus());
        $this->assertFalse($category->isActive());
    }

    public function testCategoryNormalizesOptionalFields(): void
    {
        $category = (new ArticleCategory())
            ->setShortDescription('   ')
            ->setDescription('pl', '   ')
            ->setDescription('en', '  English description  ')
            ->setIcon('  /assets/img/icon.svg  ');

        $this->assertNull($category->getShortDescription());
        $this->assertNull($category->getDescription('pl'));
        $this->assertSame('English description', $category->getDescription('en'));
        $this->assertSame('/assets/img/icon.svg', $category->getIcon());
    }

    public function testLocalizedFieldsFallbackToPolishAndName(): void
    {
        $category = (new ArticleCategory())
            ->setName('PHP')
            ->setTitle('pl', 'Programowanie w PHP')
            ->setDescription('pl', 'Opis po polsku');

        $this->assertSame('Programowanie w PHP', $category->getLocalizedTitle('de'));
        $this->assertSame('Opis po polsku', $category->getLocalizedDescription('de'));

        $category->setTitles([]);

        $this->assertSame('PHP', $category->getLocalizedTitle('de'));
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $category = new ArticleCategory();
        $this->assertSame('UTC', $category->getCreatedAt()->getTimezone()->getName());
        $this->assertSame('UTC', $category->getUpdatedAt()->getTimezone()->getName());

        $originalCreatedAt = $category->getCreatedAt();
        $originalUpdatedAt = $category->getUpdatedAt();

        usleep(1000);
        $category->onPrePersist();

        $this->assertGreaterThanOrEqual($originalCreatedAt->getTimestamp(), $category->getCreatedAt()->getTimestamp());
        $this->assertGreaterThanOrEqual($originalUpdatedAt->getTimestamp(), $category->getUpdatedAt()->getTimestamp());

        $updatedAtAfterPersist = $category->getUpdatedAt();

        usleep(1000);
        $category->onPreUpdate();

        $this->assertGreaterThanOrEqual($updatedAtAfterPersist->getTimestamp(), $category->getUpdatedAt()->getTimestamp());
        $this->assertSame('UTC', $category->getUpdatedAt()->getTimezone()->getName());
    }
}
