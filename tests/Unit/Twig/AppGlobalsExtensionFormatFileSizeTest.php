<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Repository\ArticleKeywordImportQueueRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\CategoryImportQueueRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Service\BlogSettingsProvider;
use App\Service\FileSizeFormatter;
use App\Service\TopMenuBuilder;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Twig\AppGlobalsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class AppGlobalsExtensionFormatFileSizeTest extends TestCase
{
    public function testFormatFileSizeReturnsHumanReadableValue(): void
    {
        $extension = new AppGlobalsExtension(
            $this->createStub(BlogSettingsProvider::class),
            $this->createStub(UserLanguageResolver::class),
            $this->createStub(UserTimeZoneResolver::class),
            $this->createStub(ArticleImportQueueRepository::class),
            $this->createStub(ArticleKeywordImportQueueRepository::class),
            $this->createStub(CategoryImportQueueRepository::class),
            $this->createStub(TopMenuImportQueueRepository::class),
            $this->createStub(ArticleExportQueueRepository::class),
            $this->createStub(ArticleKeywordExportQueueRepository::class),
            $this->createStub(CategoryExportQueueRepository::class),
            $this->createStub(TopMenuExportQueueRepository::class),
            $this->createStub(ArticleExportRepository::class),
            $this->createStub(TopMenuItemRepository::class),
            $this->createStub(TopMenuBuilder::class),
            new FileSizeFormatter(),
            new UploadLimitResolver(static fn (string $key): string|false => false),
            $this->createStub(CacheInterface::class),
            'test',
        );

        $this->assertSame('1.5 KB', $extension->formatFileSize(1536));
    }
}
