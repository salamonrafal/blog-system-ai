<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TranslationCatalogLoader;
use PHPUnit\Framework\TestCase;

final class TranslationCatalogLoaderTest extends TestCase
{
    public function testLoadDomainMessagesReturnsConfiguredLanguages(): void
    {
        $loader = new TranslationCatalogLoader();

        $messages = $loader->loadDomainMessages('validators');

        $this->assertArrayHasKey('pl', $messages);
        $this->assertArrayHasKey('en', $messages);
        $this->assertSame('Obrazek nie może być większy niż {{ limit }}.', $messages['pl']['validation_media_file_too_large'] ?? null);
        $this->assertSame('The image cannot be larger than {{ limit }}.', $messages['en']['validation_media_file_too_large'] ?? null);
    }

    public function testLoadLanguageMessagesFallsBackToPolish(): void
    {
        $loader = new TranslationCatalogLoader();

        $messages = $loader->loadLanguageMessages('validators', 'de');

        $this->assertSame('Obrazek nie może być większy niż {{ limit }}.', $messages['validation_media_file_too_large'] ?? null);
    }
}
