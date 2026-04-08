<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserLanguageResolver
{
    private const COOKIE_NAME = 'user_language';
    private const DEFAULT_LANGUAGE = 'pl';
    private const SUPPORTED_LANGUAGES = ['pl', 'en'];

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getLanguage(): string
    {
        return $this->resolveLanguage($this->requestStack->getCurrentRequest());
    }

    public function resolveLanguage(?Request $request): string
    {
        $language = $request?->cookies->get(self::COOKIE_NAME);

        if (!is_string($language) || '' === trim($language)) {
            return self::DEFAULT_LANGUAGE;
        }

        $language = strtolower(trim($language));

        return in_array($language, self::SUPPORTED_LANGUAGES, true)
            ? $language
            : self::DEFAULT_LANGUAGE;
    }

    public function translate(string $polish, string $english): string
    {
        return 'pl' === $this->getLanguage() ? $polish : $english;
    }
}
