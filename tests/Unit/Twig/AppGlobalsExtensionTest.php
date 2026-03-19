<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Service\BlogSettingsProvider;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Twig\AppGlobalsExtension;
use PHPUnit\Framework\TestCase;

final class AppGlobalsExtensionTest extends TestCase
{
    public function testGetGlobalsExposesAppNameAndBlogSettings(): void
    {
        $settings = (new BlogSettings())->setBlogTitle('Blog testowy');

        $provider = $this->createMock(BlogSettingsProvider::class);
        $provider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('en');

        $timeZoneResolver = $this->createMock(UserTimeZoneResolver::class);
        $timeZoneResolver
            ->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('Europe/Warsaw');

        $extension = new AppGlobalsExtension($provider, $languageResolver, $timeZoneResolver);
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame($settings, $globals['blog_settings']);
        $this->assertSame('en', $globals['user_language']);
        $this->assertSame('Europe/Warsaw', $globals['user_timezone']);
    }
}
