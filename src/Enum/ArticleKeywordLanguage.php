<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleKeywordLanguage: string
{
    case ALL = 'all';
    case PL = 'pl';
    case EN = 'en';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Dla Wszystkich',
            self::PL => 'Polski (PL)',
            self::EN => 'Angielski (EN)',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::ALL => 'article_keyword_language_all',
            self::PL => 'article_language_pl',
            self::EN => 'article_language_en',
        };
    }

    public function matchesArticleLanguage(ArticleLanguage $language): bool
    {
        return match ($this) {
            self::ALL => true,
            self::PL => ArticleLanguage::PL === $language,
            self::EN => ArticleLanguage::EN === $language,
        };
    }
}
