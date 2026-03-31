<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\BlogSettingsProvider;
use App\Service\TopMenuBuilder;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Twig\AppGlobalsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class AppGlobalsExtensionTest extends TestCase
{
    public function testGetI18nFallbackReturnsTranslatedValidationMessage(): void
    {
        $provider = $this->createMock(BlogSettingsProvider::class);
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->method('getLanguage')
            ->willReturn('en');

        $timeZoneResolver = $this->createMock(UserTimeZoneResolver::class);
        $importQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $topMenuImportQueueRepository = $this->createMock(TopMenuImportQueueRepository::class);
        $exportQueueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $categoryExportQueueRepository = $this->createMock(CategoryExportQueueRepository::class);
        $topMenuExportQueueRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $exportRepository = $this->createMock(ArticleExportRepository::class);
        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuBuilder = $this->createMock(TopMenuBuilder::class);
        $appCache = $this->createMock(CacheInterface::class);

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $timeZoneResolver,
            $importQueueRepository,
            $topMenuImportQueueRepository,
            $exportQueueRepository,
            $categoryExportQueueRepository,
            $topMenuExportQueueRepository,
            $exportRepository,
            $topMenuRepository,
            $topMenuBuilder,
            $appCache,
            'test',
        );

        $this->assertSame('Select an import file.', $extension->getI18nFallback('validation_import_file_required'));
        $this->assertSame('unknown_key', $extension->getI18nFallback('unknown_key'));
    }

    public function testGetGlobalsExposesAppNameAndBlogSettings(): void
    {
        $settings = (new BlogSettings())
            ->setAppUrl('https://blog.example.com')
            ->setBlogTitle('Blog testowy');

        $provider = $this->createMock(BlogSettingsProvider::class);
        $provider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('en');

        $timeZoneResolver = $this->createMock(UserTimeZoneResolver::class);
        $timeZoneResolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('Europe/Warsaw');

        $importQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $importQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(2);
        $topMenuImportQueueRepository = $this->createMock(TopMenuImportQueueRepository::class);
        $topMenuImportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(1);

        $exportQueueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $exportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(3);
        $categoryExportQueueRepository = $this->createMock(CategoryExportQueueRepository::class);
        $categoryExportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(1);
        $topMenuExportQueueRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $topMenuExportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(2);

        $exportRepository = $this->createMock(ArticleExportRepository::class);
        $exportRepository
            ->expects($this->once())
            ->method('countNew')
            ->willReturn(4);
        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuRepository
            ->expects($this->once())
            ->method('findActiveOrdered')
            ->willReturn([]);
        $topMenuBuilder = $this->createMock(TopMenuBuilder::class);
        $topMenuBuilder
            ->expects($this->once())
            ->method('buildActiveTree')
            ->with([])
            ->willReturn([['label' => 'Blog', 'url' => '/', 'children' => [], 'has_children' => false, 'external' => false]]);
        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();
        $appCache = $this->createMock(CacheInterface::class);
        $appCache
            ->expects($this->once())
            ->method('get')
            ->with(
                'twig.top_menu_items.en',
                $this->callback(function (callable $callback) use ($cacheItem): bool {
                    $result = $callback($cacheItem);

                    return [['label' => 'Blog', 'url' => '/', 'children' => [], 'has_children' => false, 'external' => false]] === $result;
                })
            )
            ->willReturn([['label' => 'Blog', 'url' => '/', 'children' => [], 'has_children' => false, 'external' => false]]);

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $timeZoneResolver,
            $importQueueRepository,
            $topMenuImportQueueRepository,
            $exportQueueRepository,
            $categoryExportQueueRepository,
            $topMenuExportQueueRepository,
            $exportRepository,
            $topMenuRepository,
            $topMenuBuilder,
            $appCache,
            'test',
        );
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame('https://blog.example.com', $globals['app_url']);
        $this->assertSame($settings, $globals['blog_settings']);
        $this->assertSame('test', $globals['app_env']);
        $this->assertSame('en', $globals['user_language']);
        $this->assertSame('Europe/Warsaw', $globals['user_timezone']);
        $this->assertJson($globals['validation_i18n_json']);
        $this->assertSame([
            'queue_status' => 9,
            'imports' => 3,
            'exports' => 4,
            'import_export' => 13,
        ], $globals['admin_shortcut_badges']);
        $this->assertCount(1, $globals['top_menu_items']);
    }
}
