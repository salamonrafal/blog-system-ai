<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class BaseTemplateTitleWhitespaceTest extends TestCase
{
    public function testTitleValuesAreTrimmedWhenPageTitleBlockContainsFormattingWhitespace(): void
    {
        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'test/page.html.twig' => <<<'TWIG'
{% extends 'base.html.twig' %}

{% block title %}
    {{ raw_title }} | Test Blog
{% endblock %}

{% block body %}Body{% endblock %}
TWIG,
            ]),
            new FilesystemLoader(__DIR__.'/../../../templates'),
        ]));
        $twig->addFunction(new TwigFunction('path', static fn (string $route, array $parameters = []): string => '/'.$route));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => 'token'));
        $twig->addFunction(new TwigFunction('i18n_fallback', static fn (string $id): string => $id));

        $html = $twig->render('test/page.html.twig', [
            'active_i18n_json' => '{}',
            'admin_shortcut_badges' => [
                'imports' => 0,
                'keyword_imports' => 0,
                'category_imports' => 0,
                'top_menu_imports' => 0,
                'exports' => 0,
                'import_export' => 0,
                'queue_status' => 0,
            ],
            'app' => [
                'request' => new Request(),
            ],
            'app_env' => 'test',
            'app_name' => 'Test Blog',
            'app_url' => 'https://example.com',
            'blog_settings' => new BlogSettings(),
            'i18n_catalog_version' => 'test',
            'preference_cookie_domain' => '',
            'raw_title' => 'Clean & tidy',
            'top_menu_items' => [],
            'user_language' => 'pl',
        ]);

        self::assertStringContainsString('<title>Clean &amp; tidy | Test Blog</title>', $html);
        self::assertStringContainsString('<meta property="og:title" content="Clean &amp; tidy | Test Blog">', $html);
        self::assertStringContainsString('<meta name="twitter:title" content="Clean &amp; tidy | Test Blog">', $html);
        self::assertStringContainsString('data-page-title="Clean &amp; tidy | Test Blog"', $html);
        self::assertStringNotContainsString("content=\"\n    Clean &amp; tidy | Test Blog", $html);
        self::assertStringNotContainsString("data-page-title=\"\n    Clean &amp; tidy | Test Blog", $html);
        self::assertStringNotContainsString('Clean &amp;amp; tidy', $html);
    }
}
