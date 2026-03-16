<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\ArticleLanguage;
use PHPUnit\Framework\TestCase;

final class ArticleLanguageTest extends TestCase
{
    public function testLabelReturnsExpectedTextForEachLanguage(): void
    {
        $this->assertSame('Polski (PL)', ArticleLanguage::PL->label());
        $this->assertSame('English (EN)', ArticleLanguage::EN->label());
    }
}
