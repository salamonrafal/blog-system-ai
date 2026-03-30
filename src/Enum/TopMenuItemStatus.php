<?php

declare(strict_types=1);

namespace App\Enum;

enum TopMenuItemStatus: string
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
            self::ACTIVE => 'top_menu_status_active',
            self::INACTIVE => 'top_menu_status_inactive',
        };
    }
}
