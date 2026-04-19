<?php

declare(strict_types=1);

namespace App\Service;

class TranslationCatalogLoader
{
    private const TRANSLATION_FILES = [
        'app' => [
            'pl' => __DIR__.'/../../translations/app.pl.php',
            'en' => __DIR__.'/../../translations/app.en.php',
        ],
        'validators' => [
            'pl' => __DIR__.'/../../translations/validators.pl.php',
            'en' => __DIR__.'/../../translations/validators.en.php',
        ],
    ];
    private array $domainMessagesCache = [];
    private array $languageMessagesCache = [];

    /**
     * @return array<string, array<string, string>>
     */
    public function loadDomainMessages(string $domain): array
    {
        if (isset($this->domainMessagesCache[$domain])) {
            return $this->domainMessagesCache[$domain];
        }

        $domainFiles = self::TRANSLATION_FILES[$domain] ?? [];
        $messages = [];

        foreach ($domainFiles as $language => $path) {
            $messages[$language] = $this->loadCatalogFile($path);
        }

        return $this->domainMessagesCache[$domain] = $messages;
    }

    /**
     * @return array<string, string>
     */
    public function loadLanguageMessages(string $domain, string $language, string $fallbackLanguage = 'pl'): array
    {
        $normalizedLanguage = strtolower(trim($language));
        $normalizedFallbackLanguage = strtolower(trim($fallbackLanguage));
        $cacheKey = $domain.'|'.$normalizedLanguage.'|'.$normalizedFallbackLanguage;

        if (isset($this->languageMessagesCache[$cacheKey])) {
            return $this->languageMessagesCache[$cacheKey];
        }

        $domainFiles = self::TRANSLATION_FILES[$domain] ?? [];
        $messages = isset($domainFiles[$normalizedLanguage])
            ? $this->loadCatalogFile($domainFiles[$normalizedLanguage])
            : [];

        if ($normalizedLanguage !== $normalizedFallbackLanguage && isset($domainFiles[$normalizedFallbackLanguage])) {
            $messages += $this->loadCatalogFile($domainFiles[$normalizedFallbackLanguage]);
        }

        if ([] === $messages && isset($domainFiles[$normalizedFallbackLanguage])) {
            $messages = $this->loadCatalogFile($domainFiles[$normalizedFallbackLanguage]);
        }

        return $this->languageMessagesCache[$cacheKey] = $messages;
    }

    /**
     * @param list<string> $domains
     * @return array<string, string>
     */
    public function loadMergedLanguageMessages(array $domains, string $language, string $fallbackLanguage = 'pl'): array
    {
        $messages = [];

        foreach ($domains as $domain) {
            $messages += $this->loadLanguageMessages($domain, $language, $fallbackLanguage);
        }

        return $messages;
    }

    /**
     * @return array<string, string>
     */
    private function loadCatalogFile(string $path): array
    {
        /** @var array<string, string> $messages */
        $messages = require $path;

        return $messages;
    }
}
