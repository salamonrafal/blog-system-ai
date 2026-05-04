<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'In review',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::DRAFT => 'article_status_draft',
            self::REVIEW => 'article_status_review',
            self::PUBLISHED => 'article_status_published',
            self::ARCHIVED => 'article_status_archived',
        };
    }
}
