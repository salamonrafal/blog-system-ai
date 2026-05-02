<?php

declare(strict_types=1);

namespace App\Enum;

enum AnalyticsScriptPlacement: string
{
    case HEAD = 'head';
    case BODY_END = 'body_end';

    public function label(): string
    {
        return match ($this) {
            self::HEAD => 'Head',
            self::BODY_END => 'Before </body>',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::HEAD => 'analytics_script_placement_head',
            self::BODY_END => 'analytics_script_placement_body_end',
        };
    }
}
