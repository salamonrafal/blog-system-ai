<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleLanguage: string
{
    case PL = 'pl';
    case EN = 'en';

    public function label(): string
    {
        return match ($this) {
            self::PL => 'Polski (PL)',
            self::EN => 'English (EN)',
        };
    }
}
