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
}
