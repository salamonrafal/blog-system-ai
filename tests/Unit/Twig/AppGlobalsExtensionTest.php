<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\BlogSettings;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
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

        $importQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $importQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(2);

        $exportQueueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $exportQueueRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(3);

        $exportRepository = $this->createMock(ArticleExportRepository::class);
        $exportRepository
            ->expects($this->once())
            ->method('countNew')
            ->willReturn(4);

        $extension = new AppGlobalsExtension(
            $provider,
            $languageResolver,
            $timeZoneResolver,
            $importQueueRepository,
            $exportQueueRepository,
            $exportRepository,
            'test',
        );
        $globals = $extension->getGlobals();

        $this->assertSame('Blog testowy', $globals['app_name']);
        $this->assertSame($settings, $globals['blog_settings']);
        $this->assertSame('test', $globals['app_env']);
        $this->assertSame('en', $globals['user_language']);
        $this->assertSame('Europe/Warsaw', $globals['user_timezone']);
        $this->assertSame([
            'queue_status' => 5,
            'imports' => 2,
            'exports' => 4,
            'import_export' => 9,
        ], $globals['admin_shortcut_badges']);
    }
}
