<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ArticleImportQueue;
use App\Entity\User;
use App\Enum\ArticleImportQueueStatus;
use PHPUnit\Framework\TestCase;

final class ArticleImportQueueTest extends TestCase
{
    public function testQueueItemExposesAssignedValues(): void
    {
        $processedAt = new \DateTimeImmutable('2026-03-23 12:00:00', new \DateTimeZone('Europe/Warsaw'));
        $user = (new User())
            ->setEmail('importer@example.com')
            ->setFullName('Importer');

        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('articles-export.json')
            ->setFilePath('var/imports/article-import-1.json')
            ->setRequestedBy($user)
            ->setStatus(ArticleImportQueueStatus::COMPLETED)
            ->setErrorMessage('Przykladowy blad')
            ->setProcessedAt($processedAt);

        $this->assertSame('articles-export.json', $queueItem->getOriginalFilename());
        $this->assertSame('var/imports/article-import-1.json', $queueItem->getFilePath());
        $this->assertSame($user, $queueItem->getRequestedBy());
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertSame('Przykladowy blad', $queueItem->getErrorMessage());
        $this->assertSame('2026-03-23 11:00:00', $queueItem->getProcessedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $queueItem->getProcessedAt()?->getTimezone()->getName());
    }

    public function testNonCompletedStatusClearsProcessedAt(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setProcessedAt(new \DateTimeImmutable('2026-03-23 12:00:00', new \DateTimeZone('Europe/Warsaw')))
            ->setStatus(ArticleImportQueueStatus::FAILED);

        $this->assertNull($queueItem->getProcessedAt());
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $queueItem = new ArticleImportQueue();
        $this->assertSame('UTC', $queueItem->getCreatedAt()->getTimezone()->getName());
        $this->assertSame('UTC', $queueItem->getUpdatedAt()->getTimezone()->getName());

        $originalCreatedAt = $queueItem->getCreatedAt();
        $originalUpdatedAt = $queueItem->getUpdatedAt();

        usleep(1000);
        $queueItem->onPrePersist();

        $this->assertGreaterThanOrEqual($originalCreatedAt->getTimestamp(), $queueItem->getCreatedAt()->getTimestamp());
        $this->assertGreaterThanOrEqual($originalUpdatedAt->getTimestamp(), $queueItem->getUpdatedAt()->getTimestamp());

        $updatedAtAfterPersist = $queueItem->getUpdatedAt();

        usleep(1000);
        $queueItem->onPreUpdate();

        $this->assertGreaterThanOrEqual($updatedAtAfterPersist->getTimestamp(), $queueItem->getUpdatedAt()->getTimestamp());
        $this->assertSame('UTC', $queueItem->getUpdatedAt()->getTimezone()->getName());
    }
}
