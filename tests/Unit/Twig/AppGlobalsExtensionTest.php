<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Repository\ArticleKeywordImportQueueRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\CategoryImportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\BlogSettingsProvider;
use App\Service\FileSizeFormatter;
use App\Service\TopMenuBuilder;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Twig\AppGlobalsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $keywordImportQueueRepository = $this->createMock(ArticleKeywordImportQueueRepository::class);
        $categoryImportQueueRepository = $this->createMock(CategoryImportQueueRepository::class);
        $topMenuImportQueueRepository = $this->createMock(TopMenuImportQueueRepository::class);
        $exportQueueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $keywordExportQueueRepository = $this->createMock(ArticleKeywordExportQueueRepository::class);
        $categoryExportQueueRepository = $this->createMock(CategoryExportQueueRepository::class);
        $topMenuExportQueueRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $exportRepository = $this->createMock(ArticleExportRepository::class);
        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuBuilder = $this->createMock(TopMenuBuilder::class);
        $appCache = $this->createMock(CacheInterface::class);
        $translator = new class implements TranslatorInterface {
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id ?? '';
            }

            public function getLocale(): string
            {
                return 'en';
            }

            /**
             * @return list<string>
             */
            public function getFallbackLocales(): array
            {
                return ['pl'];
            }
        };

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $timeZoneResolver,
            $importQueueRepository,
            $keywordImportQueueRepository,
            $categoryImportQueueRepository,
            $topMenuImportQueueRepository,
            $exportQueueRepository,
            $keywordExportQueueRepository,
            $categoryExportQueueRepository,
            $topMenuExportQueueRepository,
            $exportRepository,
            $topMenuRepository,
            $topMenuBuilder,
            new FileSizeFormatter(),
            new UploadLimitResolver(static fn (string $key): string|false => false),
            $appCache,
            'test',
            $translator,
        );

        $this->assertSame('Select an import file.', $extension->getI18nFallback('validation_import_file_required'));
        $this->assertSame('The import file cannot be larger than 10 MB.', $extension->getI18nFallback('validation_import_file_too_large'));
        $this->assertSame('The image cannot be larger than {{ limit }}.', $extension->getI18nFallback('validation_media_file_too_large'));
        $this->assertSame('{{count}} powiadomienia', $extension->getI18nFallback('admin_shortcut_notifications_badge_few'));
        $this->assertSame('unknown_key', $extension->getI18nFallback('unknown_key'));
        $this->assertSame('Category import', $extension->translateUi('Import kategorii', 'Category import'));
        $this->assertSame('English (EN)', $extension->getLanguageLabel('en'));
    }

    public function testTranslateValidationErrorInterpolatesTranslatedSymfonyMessage(): void
    {
        $provider = $this->createMock(BlogSettingsProvider::class);
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->method('getLanguage')
            ->willReturn('pl');

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $this->createMock(UserTimeZoneResolver::class),
            $this->createMock(ArticleImportQueueRepository::class),
            $this->createMock(ArticleKeywordImportQueueRepository::class),
            $this->createMock(CategoryImportQueueRepository::class),
            $this->createMock(TopMenuImportQueueRepository::class),
            $this->createMock(ArticleExportQueueRepository::class),
            $this->createMock(ArticleKeywordExportQueueRepository::class),
            $this->createMock(CategoryExportQueueRepository::class),
            $this->createMock(TopMenuExportQueueRepository::class),
            $this->createMock(ArticleExportRepository::class),
            $this->createMock(TopMenuItemRepository::class),
            $this->createMock(TopMenuBuilder::class),
            new FileSizeFormatter(),
            new UploadLimitResolver(static fn (string $key): string|false => false),
            $this->createMock(CacheInterface::class),
            'test',
        );

        $error = new FormError(
            'The file is too large. Allowed maximum size is 2 MiB.',
            'The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.',
            ['{{ limit }}' => '2', '{{ suffix }}' => 'MiB'],
        );

        $this->assertSame(
            'Plik jest za duży. Maksymalny dozwolony rozmiar to 2 MiB.',
            $extension->translateValidationError($error),
        );
        $this->assertSame(
            'The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.',
            $extension->getValidationErrorI18nKey($error),
        );
        $this->assertSame(
            ['limit' => '2', 'suffix' => 'MiB'],
            $extension->getValidationErrorI18nParams($error),
        );
    }

    public function testGetGlobalsExposesAppNameAndBlogSettings(): void
    {
        $settings = (new BlogSettings())
            ->setAppUrl('https://blog.example.com')
            ->setPreferenceCookieDomainOverride('.example.com')
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
        $keywordImportQueueRepository = $this->createMock(ArticleKeywordImportQueueRepository::class);
        $keywordImportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(4);
        $categoryImportQueueRepository = $this->createMock(CategoryImportQueueRepository::class);
        $categoryImportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(3);
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
        $keywordExportQueueRepository = $this->createMock(ArticleKeywordExportQueueRepository::class);
        $keywordExportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(5);
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
            $keywordImportQueueRepository,
            $categoryImportQueueRepository,
            $topMenuImportQueueRepository,
            $exportQueueRepository,
            $keywordExportQueueRepository,
            $categoryExportQueueRepository,
            $topMenuExportQueueRepository,
            $exportRepository,
            $topMenuRepository,
            $topMenuBuilder,
            new FileSizeFormatter(),
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            $appCache,
            'test',
        );
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame('https://blog.example.com', $globals['app_url']);
        $this->assertSame('.example.com', $globals['preference_cookie_domain']);
        $this->assertSame($settings, $globals['blog_settings']);
        $this->assertSame('test', $globals['app_env']);
        $this->assertSame('en', $globals['user_language']);
        $this->assertSame('Europe/Warsaw', $globals['user_timezone']);
        $this->assertSame(2 * 1024 * 1024, $globals['media_upload_limit_bytes']);
        $this->assertSame('2.0 MB', $globals['media_upload_limit_formatted']);
        $this->assertJson($globals['active_i18n_json']);
        $activeI18n = json_decode($globals['active_i18n_json'], true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('Select an import file.', $activeI18n['validation_import_file_required'] ?? null);
        $this->assertSame('{{count}} powiadomienia', $activeI18n['admin_shortcut_notifications_badge_few'] ?? null);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $globals['i18n_catalog_version']);
        $this->assertSame([
            'queue_status' => 21,
            'imports' => 2,
            'keyword_imports' => 4,
            'category_imports' => 3,
            'top_menu_imports' => 1,
            'exports' => 4,
            'import_export' => 25,
        ], $globals['admin_shortcut_badges']);
        $this->assertCount(1, $globals['top_menu_items']);
    }
}
