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
            self::ACTIVE => 'Aktywna',
            self::INACTIVE => 'Nieaktywna',
        };
    }
}
