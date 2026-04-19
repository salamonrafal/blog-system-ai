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
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Repository\ArticleKeywordImportQueueRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\CategoryImportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    private const TRANSLATION_FILES = [
        'app' => [
            'pl' => __DIR__ . '/../../translations/app.pl.php',
            'en' => __DIR__ . '/../../translations/app.en.php',
        ],
        'validators' => [
            'pl' => __DIR__ . '/../../translations/validators.pl.php',
            'en' => __DIR__ . '/../../translations/validators.en.php',
        ],
    ];
    private const TOP_MENU_CACHE_KEY_PREFIX = 'twig.top_menu_items.';

    private ?array $appMessageFallbacks = null;
    private ?array $validationMessageFallbacks = null;
    private array $resolvedI18nFallbackMessages = [];

    public function __construct(
        private readonly BlogSettingsProvider $blogSettingsProvider,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
        private readonly ArticleImportQueueRepository $articleImportQueueRepository,
        private readonly ArticleKeywordImportQueueRepository $articleKeywordImportQueueRepository,
        private readonly CategoryImportQueueRepository $categoryImportQueueRepository,
        private readonly TopMenuImportQueueRepository $topMenuImportQueueRepository,
        private readonly ArticleExportQueueRepository $articleExportQueueRepository,
        private readonly ArticleKeywordExportQueueRepository $articleKeywordExportQueueRepository,
        private readonly CategoryExportQueueRepository $categoryExportQueueRepository,
        private readonly TopMenuExportQueueRepository $topMenuExportQueueRepository,
        private readonly ArticleExportRepository $articleExportRepository,
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly TopMenuBuilder $topMenuBuilder,
        private readonly FileSizeFormatter $fileSizeFormatter,
        private readonly UploadLimitResolver $uploadLimitResolver,
        private readonly CacheInterface $appCache,
        private readonly string $appEnv,
        private readonly ?TranslatorInterface $translator = null,
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
            new TwigFunction('validation_error_message', $this->translateValidationError(...)),
            new TwigFunction('validation_error_i18n_key', $this->getValidationErrorI18nKey(...)),
            new TwigFunction('validation_error_i18n_params', $this->getValidationErrorI18nParams(...)),
        ];
    }

    public function getGlobals(): array
    {
        $settings = $this->blogSettingsProvider->getSettings();
        $pendingArticleImportCount = $this->articleImportQueueRepository->countPending();
        $pendingKeywordImportCount = $this->articleKeywordImportQueueRepository->countPending();
        $pendingCategoryImportCount = $this->categoryImportQueueRepository->countPending();
        $pendingTopMenuImportCount = $this->topMenuImportQueueRepository->countPending();
        $pendingImportCount = $pendingArticleImportCount + $pendingKeywordImportCount + $pendingCategoryImportCount + $pendingTopMenuImportCount;
        $pendingExportQueueCount = $this->articleExportQueueRepository->countPending()
            + $this->articleKeywordExportQueueRepository->countPending()
            + $this->categoryExportQueueRepository->countPending()
            + $this->topMenuExportQueueRepository->countPending();
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
            'preference_cookie_domain' => $settings->getPreferenceCookieDomain() ?? '',
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
            'app_i18n_json' => json_encode(
                $this->getAppMessageFallbacks(),
                \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES
            ) ?: '{"pl":{},"en":{}}',
            'admin_shortcut_badges' => [
                'queue_status' => $pendingImportCount + $pendingExportQueueCount,
                'imports' => $pendingArticleImportCount,
                'keyword_imports' => $pendingKeywordImportCount,
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
        $messages = $this->getResolvedI18nFallbackMessages();

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

    public function translateValidationError(mixed $error): string
    {
        if (!$error instanceof FormError) {
            return is_string($error) ? $this->getI18nFallback($error) : '';
        }

        $messageTemplate = $this->getValidationErrorI18nKey($error);
        $messageParameters = $error->getMessageParameters();

        if (null !== $this->translator) {
            return $this->translator->trans(
                $messageTemplate,
                $messageParameters,
                'validators',
                $this->userLanguageResolver->getLanguage(),
            );
        }

        return strtr($this->getI18nFallback($messageTemplate), $messageParameters);
    }

    public function getValidationErrorI18nKey(mixed $error): string
    {
        if ($error instanceof FormError) {
            return $error->getMessageTemplate();
        }

        return is_string($error) ? $error : '';
    }

    /**
     * @return array<string, string>
     */
    public function getValidationErrorI18nParams(mixed $error): array
    {
        if (!$error instanceof FormError) {
            return [];
        }

        $parameters = [];

        foreach ($error->getMessageParameters() as $name => $value) {
            $normalizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string) $name, "{} \t\n\r\0\x0B"));
            if (!is_string($normalizedName) || '' === $normalizedName) {
                continue;
            }

            $parameters[$normalizedName] = (string) $value;
        }

        return $parameters;
    }

    private function getValidationMessageFallbacks(): array
    {
        if (null !== $this->validationMessageFallbacks) {
            return $this->validationMessageFallbacks;
        }

        return $this->validationMessageFallbacks = $this->loadDomainMessages('validators');
    }

    private function getAppMessageFallbacks(): array
    {
        if (null !== $this->appMessageFallbacks) {
            return $this->appMessageFallbacks;
        }

        return $this->appMessageFallbacks = $this->loadDomainMessages('app');
    }

    /**
     * @return array<string, string>
     */
    private function getResolvedI18nFallbackMessages(): array
    {
        $languages = $this->resolveFallbackLanguages();
        $cacheKey = implode('|', $languages);

        if (isset($this->resolvedI18nFallbackMessages[$cacheKey])) {
            return $this->resolvedI18nFallbackMessages[$cacheKey];
        }

        $messages = [];

        foreach ($languages as $language) {
            $messages += ($this->getAppMessageFallbacks()[$language] ?? [])
                + ($this->getValidationMessageFallbacks()[$language] ?? []);
        }

        return $this->resolvedI18nFallbackMessages[$cacheKey] = $messages;
    }

    /**
     * @return list<string>
     */
    private function resolveFallbackLanguages(): array
    {
        $languages = [$this->userLanguageResolver->getLanguage()];

        if (null !== $this->translator && method_exists($this->translator, 'getFallbackLocales')) {
            /** @var mixed $fallbackLocales */
            $fallbackLocales = $this->translator->getFallbackLocales();
            if (is_array($fallbackLocales)) {
                foreach ($fallbackLocales as $fallbackLocale) {
                    if (is_string($fallbackLocale) && '' !== trim($fallbackLocale)) {
                        $languages[] = $fallbackLocale;
                    }
                }
            }
        }

        $normalizedLanguages = [];

        foreach ($languages as $language) {
            $normalizedLanguage = strtolower(trim($language));
            if ('' === $normalizedLanguage || in_array($normalizedLanguage, $normalizedLanguages, true)) {
                continue;
            }

            $normalizedLanguages[] = $normalizedLanguage;
        }

        return $normalizedLanguages;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function loadDomainMessages(string $domain): array
    {
        $domainFiles = self::TRANSLATION_FILES[$domain] ?? [];

        $messages = [];

        foreach ($domainFiles as $language => $path) {
            /** @var array<string, string> $languageMessages */
            $languageMessages = require $path;
            $messages[$language] = $languageMessages;
        }

        return $messages;
    }
}
