<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ArticleExport;
use App\Entity\User;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use PHPUnit\Framework\TestCase;

final class ArticleExportTest extends TestCase
{
    public function testExportExposesAssignedValues(): void
    {
        $articleExport = (new ArticleExport())
            ->setRequestedBy(
                (new User())
                    ->setEmail('exporter@example.com')
                    ->setFullName('Eksporter')
            )
            ->setStatus(ArticleExportStatus::NEW)
            ->setType(ArticleExportType::ARTICLES)
            ->setFilePath('var/exports/articles-export.json')
            ->setItemsCount(3);

        $this->assertSame('Eksporter', $articleExport->getRequestedBy()?->getDisplayName());
        $this->assertSame(ArticleExportStatus::NEW, $articleExport->getStatus());
        $this->assertSame(ArticleExportType::ARTICLES, $articleExport->getType());
        $this->assertSame('var/exports/articles-export.json', $articleExport->getFilePath());
        $this->assertSame(3, $articleExport->getItemsCount());
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $articleExport = new ArticleExport();
        $this->assertSame('UTC', $articleExport->getCreatedAt()->getTimezone()->getName());
        $this->assertSame('UTC', $articleExport->getUpdatedAt()->getTimezone()->getName());

        $originalCreatedAt = $articleExport->getCreatedAt();
        $originalUpdatedAt = $articleExport->getUpdatedAt();

        usleep(1000);
        $articleExport->onPrePersist();

        $this->assertGreaterThanOrEqual($originalCreatedAt->getTimestamp(), $articleExport->getCreatedAt()->getTimestamp());
        $this->assertGreaterThanOrEqual($originalUpdatedAt->getTimestamp(), $articleExport->getUpdatedAt()->getTimestamp());

        $updatedAtAfterPersist = $articleExport->getUpdatedAt();

        usleep(1000);
        $articleExport->onPreUpdate();

        $this->assertGreaterThanOrEqual($updatedAtAfterPersist->getTimestamp(), $articleExport->getUpdatedAt()->getTimestamp());
        $this->assertSame('UTC', $articleExport->getUpdatedAt()->getTimezone()->getName());
    }
}
