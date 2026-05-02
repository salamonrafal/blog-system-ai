<?php

declare(strict_types=1);

namespace App\Enum;

enum AnalyticsScriptScope: string
{
    case ALL_PUBLIC = 'all_public';
    case BLOG_HOME = 'blog_home';
    case ARTICLE = 'article';
    case CATEGORY = 'category';
    case KEYWORD = 'keyword';

    public function label(): string
    {
        return match ($this) {
            self::ALL_PUBLIC => 'All public pages',
            self::BLOG_HOME => 'Main blog page',
            self::ARTICLE => 'Articles',
            self::CATEGORY => 'Categories',
            self::KEYWORD => 'Keywords',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::ALL_PUBLIC => 'analytics_script_scope_all_public',
            self::BLOG_HOME => 'analytics_script_scope_blog_home',
            self::ARTICLE => 'analytics_script_scope_article',
            self::CATEGORY => 'analytics_script_scope_category',
            self::KEYWORD => 'analytics_script_scope_keyword',
        };
    }

    public static function fromRouteName(string $routeName): ?self
    {
        return match ($routeName) {
            'blog_index' => self::BLOG_HOME,
            'blog_show' => self::ARTICLE,
            'blog_category' => self::CATEGORY,
            'blog_keyword' => self::KEYWORD,
            default => null,
        };
    }
}
