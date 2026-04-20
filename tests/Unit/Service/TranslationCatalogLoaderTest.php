<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TranslationCatalogLoader;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;

final class TranslationCatalogLoaderTest extends TestCase
{
    public function testLoadDomainMessagesReturnsConfiguredLanguages(): void
    {
        $loader = new TranslationCatalogLoader();

        $messages = $loader->loadDomainMessages('validators');

        $this->assertSame(UserLanguageResolver::supportedLanguages(), array_keys($messages));
        $this->assertSame('Obrazek nie może być większy niż {{ limit }}.', $messages['pl']['validation_media_file_too_large'] ?? null);
        $this->assertSame('The image cannot be larger than {{ limit }}.', $messages['en']['validation_media_file_too_large'] ?? null);
    }

    public function testLoadLanguageMessagesFallsBackToPolish(): void
    {
        $loader = new TranslationCatalogLoader();

        $messages = $loader->loadLanguageMessages('validators', 'de');

        $this->assertSame('Obrazek nie może być większy niż {{ limit }}.', $messages['validation_media_file_too_large'] ?? null);
    }

    public function testLoadLanguageMessagesMergesRequestedLanguageWithPolishFallback(): void
    {
        $loader = new TranslationCatalogLoader();

        $messages = $loader->loadLanguageMessages('app', 'en');

        $this->assertSame('Category import', $messages['admin_category_imports_title'] ?? null);
        $this->assertSame('{{count}} powiadomienia', $messages['admin_shortcut_notifications_badge_few'] ?? null);
    }
}
