<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Twig\UserDateExtension;
use PHPUnit\Framework\TestCase;

final class UserDateExtensionTest extends TestCase
{
    public function testFormatDateUsesResolvedUserTimeZone(): void
    {
        $resolver = $this->createMock(UserTimeZoneResolver::class);
        $resolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('Europe/Warsaw');

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $extension = new UserDateExtension($resolver, $languageResolver);
        $dateTime = new \DateTimeImmutable('2026-03-18 12:00:00', new \DateTimeZone('UTC'));

        $this->assertSame('18.03.2026, 13:00', $extension->formatDate($dateTime));
    }

    public function testFormatDateReturnsEmptyStringForNull(): void
    {
        $resolver = $this->createMock(UserTimeZoneResolver::class);
        $resolver
            ->expects($this->never())
            ->method('getTimeZone');

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->never())
            ->method('getLanguage');

        $extension = new UserDateExtension($resolver, $languageResolver);

        $this->assertSame('', $extension->formatDate(null));
    }

    public function testFormatDateUsesEnglishVariantFormats(): void
    {
        $resolver = $this->createMock(UserTimeZoneResolver::class);
        $resolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('America/New_York');

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('en');

        $extension = new UserDateExtension($resolver, $languageResolver);
        $dateTime = new \DateTimeImmutable('2026-03-18 12:00:00', new \DateTimeZone('UTC'));

        $this->assertSame('Mar 18, 2026', $extension->formatDate($dateTime, 'date'));
    }

    public function testFormatDateAcceptsExplicitCustomFormat(): void
    {
        $resolver = $this->createMock(UserTimeZoneResolver::class);
        $resolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('Europe/Warsaw');

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $extension = new UserDateExtension($resolver, $languageResolver);
        $dateTime = new \DateTimeImmutable('2026-03-18 12:00:00', new \DateTimeZone('UTC'));

        $this->assertSame('2026/03/18 13:00', $extension->formatDate($dateTime, 'Y/m/d H:i'));
    }
}
