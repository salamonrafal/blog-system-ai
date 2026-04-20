<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FileSizeFormatter;
use App\Service\ImageUploadConstraintFactory;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;

final class ImageUploadConstraintFactoryTest extends TestCase
{
    public function testBuildPostMaxSizeMessageFallsBackToEnglishValidationMessages(): void
    {
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->method('getLanguage')
            ->willReturn('en');

        $factory = new ImageUploadConstraintFactory(
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            new FileSizeFormatter(),
            $userLanguageResolver,
        );

        $this->assertSame('The image cannot be larger than 2.0 MB.', $factory->buildPostMaxSizeMessage());
    }

    public function testBuildPostMaxSizeMessageFallsBackToPolishForUnsupportedLanguage(): void
    {
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->method('getLanguage')
            ->willReturn('de');

        $factory = new ImageUploadConstraintFactory(
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            new FileSizeFormatter(),
            $userLanguageResolver,
        );

        $this->assertSame('Obrazek nie może być większy niż 2.0 MB.', $factory->buildPostMaxSizeMessage());
    }
}
