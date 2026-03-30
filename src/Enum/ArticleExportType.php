<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleExportType: string
{
    case ARTICLES = 'articles';
    case CATEGORIES = 'categories';

    public function label(): string
    {
        return match ($this) {
            self::ARTICLES => 'Eksport artykułów',
            self::CATEGORIES => 'Eksport kategorii',
        };
    }

    public function englishLabel(): string
    {
        return match ($this) {
            self::ARTICLES => 'Article export',
            self::CATEGORIES => 'Category export',
        };
    }

    public function localizedLabel(?string $language): string
    {
        return 'en' === strtolower(trim((string) $language))
            ? $this->englishLabel()
            : $this->label();
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::ARTICLES => 'admin_exports_type_articles',
            self::CATEGORIES => 'admin_exports_type_categories',
        };
    }

    public function queueTranslationKey(): string
    {
        return match ($this) {
            self::ARTICLES => 'admin_queue_type_article_export',
            self::CATEGORIES => 'admin_queue_type_category_export',
        };
    }
}
