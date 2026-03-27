<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\BlogSettings;
use App\Repository\BlogSettingsRepository;
use App\Service\BlogSettingsProvider;
use PHPUnit\Framework\TestCase;

final class BlogSettingsProviderTest extends TestCase
{
    public function testGetSettingsReturnsRepositoryValueAndCachesIt(): void
    {
        $settings = (new BlogSettings())->setBlogTitle('Cached blog');

        $repository = $this->createMock(BlogSettingsRepository::class);
        $repository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn($settings);

        $provider = new BlogSettingsProvider($repository);

        $this->assertSame($settings, $provider->getSettings());
        $this->assertSame($settings, $provider->getSettings());
    }

    public function testGetSettingsReturnsDefaultSettingsWhenRepositoryIsEmpty(): void
    {
        $repository = $this->createMock(BlogSettingsRepository::class);
        $repository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn(null);

        $provider = new BlogSettingsProvider($repository);
        $settings = $provider->getSettings();

        $this->assertInstanceOf(BlogSettings::class, $settings);
        $this->assertSame(BlogSettings::DEFAULT_BLOG_TITLE, $settings->getBlogTitle());
        $this->assertSame(BlogSettings::DEFAULT_ARTICLES_PER_PAGE, $settings->getArticlesPerPage());
        $this->assertSame(BlogSettings::DEFAULT_ADMIN_ARTICLES_PER_PAGE, $settings->getAdminArticlesPerPage());
    }
}
