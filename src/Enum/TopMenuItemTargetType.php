<?php

declare(strict_types=1);

namespace App\Enum;

enum TopMenuItemTargetType: string
{
    case NONE = 'none';
    case EXTERNAL_URL = 'external_url';
    case BLOG_HOME = 'blog_home';
    case ARTICLE_CATEGORY = 'article_category';
    case ARTICLE = 'article';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::EXTERNAL_URL => 'External URL',
            self::BLOG_HOME => 'Blog homepage',
            self::ARTICLE_CATEGORY => 'Article category',
            self::ARTICLE => 'Article',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::NONE => 'top_menu_target_none',
            self::EXTERNAL_URL => 'top_menu_target_external_url',
            self::BLOG_HOME => 'top_menu_target_blog_home',
            self::ARTICLE_CATEGORY => 'top_menu_target_article_category',
            self::ARTICLE => 'top_menu_target_article',
        };
    }
}
