<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\ArticleKeywordLanguage;
use App\Enum\ArticleLanguage;
use PHPUnit\Framework\TestCase;

final class ArticleKeywordLanguageTest extends TestCase
{
    public function testLabelReturnsExpectedTextForEachLanguageScope(): void
    {
        $this->assertSame('Dla Wszystkich', ArticleKeywordLanguage::ALL->label());
        $this->assertSame('Polski (PL)', ArticleKeywordLanguage::PL->label());
        $this->assertSame('English (EN)', ArticleKeywordLanguage::EN->label());
    }

    public function testMatchesArticleLanguageAllowsSharedKeywords(): void
    {
        $this->assertTrue(ArticleKeywordLanguage::ALL->matchesArticleLanguage(ArticleLanguage::PL));
        $this->assertTrue(ArticleKeywordLanguage::ALL->matchesArticleLanguage(ArticleLanguage::EN));
        $this->assertTrue(ArticleKeywordLanguage::PL->matchesArticleLanguage(ArticleLanguage::PL));
        $this->assertFalse(ArticleKeywordLanguage::PL->matchesArticleLanguage(ArticleLanguage::EN));
    }
}
