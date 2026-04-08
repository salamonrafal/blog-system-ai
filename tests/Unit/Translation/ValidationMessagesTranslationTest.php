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

        $this->assertSame('Obrazek nie może być większy niż 5 MB.', $messages['validation_media_file_too_large'] ?? null);
    }

    public function testEnglishValidatorsCatalogContainsMediaUploadLimitMessage(): void
    {
        /** @var array<string, string> $messages */
        $messages = require __DIR__.'/../../../translations/validators.en.php';

        $this->assertSame('The image cannot be larger than 5 MB.', $messages['validation_media_file_too_large'] ?? null);
    }
}
