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

    /**
     * @return array<string, array<string, string>>
     */
    public function loadDomainMessages(string $domain): array
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

    /**
     * @return array<string, string>
     */
    public function loadLanguageMessages(string $domain, string $language, string $fallbackLanguage = 'pl'): array
    {
        $normalizedLanguage = strtolower(trim($language));
        $normalizedFallbackLanguage = strtolower(trim($fallbackLanguage));
        $messagesByLanguage = $this->loadDomainMessages($domain);

        return $messagesByLanguage[$normalizedLanguage]
            ?? $messagesByLanguage[$normalizedFallbackLanguage]
            ?? [];
    }
}
