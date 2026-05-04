<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function testLabelReturnsExpectedTextForEachStatus(): void
    {
        $this->assertSame('Draft', ArticleStatus::DRAFT->label());
        $this->assertSame('In review', ArticleStatus::REVIEW->label());
        $this->assertSame('Published', ArticleStatus::PUBLISHED->label());
        $this->assertSame('Archived', ArticleStatus::ARCHIVED->label());
    }

    public function testTranslationKeyReturnsExpectedKeyForEachStatus(): void
    {
        $this->assertSame('article_status_draft', ArticleStatus::DRAFT->translationKey());
        $this->assertSame('article_status_review', ArticleStatus::REVIEW->translationKey());
        $this->assertSame('article_status_published', ArticleStatus::PUBLISHED->translationKey());
        $this->assertSame('article_status_archived', ArticleStatus::ARCHIVED->translationKey());
    }
}
