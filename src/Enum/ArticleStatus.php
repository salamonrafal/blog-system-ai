<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case REVIEW = 'review';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'In review',
            self::PUBLISHED => 'Published',
        };
    }
}
