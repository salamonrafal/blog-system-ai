<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleCategoryStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::ACTIVE => 'category_status_active',
            self::INACTIVE => 'category_status_inactive',
        };
    }
}
