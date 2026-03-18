<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Service\BlogSettingsProvider;
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

        $extension = new AppGlobalsExtension($provider);
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame($settings, $globals['blog_settings']);
    }
}
