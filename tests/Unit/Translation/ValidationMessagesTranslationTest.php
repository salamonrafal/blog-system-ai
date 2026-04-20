<?php

declare(strict_types=1);

namespace App\Tests\Unit\Translation;

use PHPUnit\Framework\TestCase;

final class ValidationMessagesTranslationTest extends TestCase
{
    public function testPolishValidatorsCatalogContainsMediaUploadLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.pl.php';

        $this->assertSame('Obrazek nie może być większy niż {{ limit }}.', $messages['validation_media_file_too_large'] ?? null);
    }

    public function testEnglishValidatorsCatalogContainsMediaUploadLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.en.php';

        $this->assertSame('The image cannot be larger than {{ limit }}.', $messages['validation_media_file_too_large'] ?? null);
    }

    public function testPolishValidatorsCatalogContainsSymfonyUploadIniLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.pl.php';

        $this->assertSame(
            'Plik jest za duży. Maksymalny dozwolony rozmiar to {{ limit }} {{ suffix }}.',
            $messages['The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.'] ?? null,
        );
    }

    public function testEnglishValidatorsCatalogContainsTopMenuParentWithChildrenMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.en.php';

        $this->assertSame(
            'A menu item that already has children cannot be moved into a submenu.',
            $messages['validation_top_menu_parent_with_children'] ?? null,
        );
    }

    public function testPolishValidatorsCatalogContainsDynamicImportUploadLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.pl.php';

        $this->assertSame(
            'Plik importu nie może być większy niż {{ limit }}.',
            $messages['validation_import_file_too_large_dynamic'] ?? null,
        );
    }

    public function testEnglishValidatorsCatalogContainsDynamicImportUploadLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.en.php';

        $this->assertSame(
            'The import file cannot be larger than {{ limit }}.',
            $messages['validation_import_file_too_large_dynamic'] ?? null,
        );
    }
}
