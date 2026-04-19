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
    private array $catalogVersionCache = [];

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
     * @param list<string> $domains
     */
    public function getCatalogVersion(array $domains): string
    {
        $normalizedDomains = array_values(array_unique(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            $domains,
        )));
        sort($normalizedDomains);
        $cacheKey = implode('|', $normalizedDomains);

        if (isset($this->catalogVersionCache[$cacheKey])) {
            return $this->catalogVersionCache[$cacheKey];
        }

        $fingerprintParts = [];

        foreach ($normalizedDomains as $domain) {
            foreach (self::TRANSLATION_FILES[$domain] ?? [] as $language => $path) {
                [$mtime, $size] = $this->readCatalogFileMetadata($path);
                $fingerprintParts[] = implode(':', [
                    $domain,
                    $language,
                    (string) $mtime,
                    (string) $size,
                ]);
            }
        }

        return $this->catalogVersionCache[$cacheKey] = sha1(implode('|', $fingerprintParts));
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

    /**
     * @return array{0:int, 1:int}
     */
    private function readCatalogFileMetadata(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Translation catalog file "%s" was not found.', $path));
        }

        $mtime = filemtime($path);
        $size = filesize($path);

        if (false === $mtime || false === $size) {
            throw new \RuntimeException(sprintf('Translation catalog file metadata for "%s" could not be read.', $path));
        }

        return [$mtime, $size];
    }
}
