<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\BlogSettingsProvider;
use App\Service\FileSizeFormatter;
use App\Service\TopMenuBuilder;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\CategoryImportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    private const VALIDATION_I18N_FILE = __DIR__ . '/../../config/validation_i18n.php';
    private const TOP_MENU_CACHE_KEY_PREFIX = 'twig.top_menu_items.';

    private ?array $validationMessageFallbacks = null;

    public function __construct(
        private readonly BlogSettingsProvider $blogSettingsProvider,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
        private readonly ArticleImportQueueRepository $articleImportQueueRepository,
        private readonly CategoryImportQueueRepository $categoryImportQueueRepository,
        private readonly TopMenuImportQueueRepository $topMenuImportQueueRepository,
        private readonly ArticleExportQueueRepository $articleExportQueueRepository,
        private readonly CategoryExportQueueRepository $categoryExportQueueRepository,
        private readonly TopMenuExportQueueRepository $topMenuExportQueueRepository,
        private readonly ArticleExportRepository $articleExportRepository,
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly TopMenuBuilder $topMenuBuilder,
        private readonly FileSizeFormatter $fileSizeFormatter,
        private readonly UploadLimitResolver $uploadLimitResolver,
        private readonly CacheInterface $appCache,
        private readonly string $appEnv,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_fallback', $this->getI18nFallback(...)),
            new TwigFunction('ui_translate', $this->translateUi(...)),
            new TwigFunction('ui_language_label', $this->getLanguageLabel(...)),
            new TwigFunction('format_file_size', $this->formatFileSize(...)),
        ];
    }

    public function getGlobals(): array
    {
        $settings = $this->blogSettingsProvider->getSettings();
        $pendingArticleImportCount = $this->articleImportQueueRepository->countPending();
        $pendingCategoryImportCount = $this->categoryImportQueueRepository->countPending();
        $pendingTopMenuImportCount = $this->topMenuImportQueueRepository->countPending();
        $pendingImportCount = $pendingArticleImportCount + $pendingCategoryImportCount + $pendingTopMenuImportCount;
        $pendingExportQueueCount = $this->articleExportQueueRepository->countPending() + $this->categoryExportQueueRepository->countPending() + $this->topMenuExportQueueRepository->countPending();
        $newExportCount = $this->articleExportRepository->countNew();
        $language = $this->userLanguageResolver->getLanguage();
        $mediaUploadLimitBytes = $this->uploadLimitResolver->resolveEffectiveLimit(\App\Service\MediaImageSupport::MAX_FILE_SIZE);
        $topMenuItems = $this->appCache->get(
            self::topMenuCacheKey($language),
            function (ItemInterface $item): array {
                $item->expiresAfter(3600);

                return $this->topMenuBuilder->buildActiveTree($this->topMenuItemRepository->findActiveOrdered());
            }
        );

        return [
            'app_name' => $settings->getBlogTitle(),
            'app_url' => $settings->getAppUrl(),
            'blog_settings' => $settings,
            'app_env' => $this->appEnv,
            'user_language' => $language,
            'user_timezone' => $this->userTimeZoneResolver->getTimeZone(),
            'media_upload_limit_bytes' => $mediaUploadLimitBytes,
            'media_upload_limit_formatted' => null !== $mediaUploadLimitBytes ? $this->formatFileSize($mediaUploadLimitBytes) : '',
            'validation_i18n_json' => json_encode(
                $this->getValidationMessageFallbacks(),
                \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES
            ) ?: '{"pl":{},"en":{}}',
            'admin_shortcut_badges' => [
                'queue_status' => $pendingImportCount + $pendingExportQueueCount,
                'imports' => $pendingArticleImportCount,
                'category_imports' => $pendingCategoryImportCount,
                'top_menu_imports' => $pendingTopMenuImportCount,
                'exports' => $newExportCount,
                'import_export' => $pendingImportCount + $pendingExportQueueCount + $newExportCount,
            ],
            'top_menu_items' => $topMenuItems,
        ];
    }

    public static function topMenuCacheKeys(): array
    {
        return array_values(self::topMenuCacheKeysByLanguage());
    }

    /**
     * @return array<string, string>
     */
    public static function topMenuCacheKeysByLanguage(): array
    {
        return [
            'pl' => self::topMenuCacheKey('pl'),
            'en' => self::topMenuCacheKey('en'),
        ];
    }

    public static function topMenuCacheKey(string $language): string
    {
        return self::TOP_MENU_CACHE_KEY_PREFIX.strtolower(trim($language));
    }

    public function getI18nFallback(string $key): string
    {
        $language = $this->userLanguageResolver->getLanguage();
        $fallbacks = $this->getValidationMessageFallbacks();
        $messages = $fallbacks[$language] ?? $fallbacks['en'] ?? [];

        return $messages[$key] ?? $key;
    }

    public function translateUi(string $pl, string $en): string
    {
        return 'en' === $this->userLanguageResolver->getLanguage() ? $en : $pl;
    }

    public function getLanguageLabel(string $language): string
    {
        return match (strtolower(trim($language))) {
            'pl' => $this->translateUi('Polski (PL)', 'Polish (PL)'),
            'en' => $this->translateUi('Angielski (EN)', 'English (EN)'),
            default => strtoupper(trim($language)),
        };
    }

    public function formatFileSize(int $bytes): string
    {
        return $this->fileSizeFormatter->format($bytes);
    }

    private function getValidationMessageFallbacks(): array
    {
        if (null !== $this->validationMessageFallbacks) {
            return $this->validationMessageFallbacks;
        }

        /** @var array<string, array<string, string>> $messages */
        $messages = require self::VALIDATION_I18N_FILE;

        return $this->validationMessageFallbacks = $messages;
    }
}
