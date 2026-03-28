<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Entity\ArticleExportQueue;
use App\Entity\User;
use App\Enum\ArticleExportQueueStatus;
use PHPUnit\Framework\TestCase;

final class ArticleExportQueueTest extends TestCase
{
    public function testQueueItemExposesAssignedValues(): void
    {
        $article = (new Article())
            ->setTitle('Tytul artykulu')
            ->setSlug('tytul-artykulu');
        $user = (new User())
            ->setEmail('exporter@example.com')
            ->setFullName('Eksporter');

        $processedAt = new \DateTimeImmutable('2026-03-22 12:00:00', new \DateTimeZone('Europe/Warsaw'));

        $queueItem = (new ArticleExportQueue($article))
            ->setRequestedBy($user)
            ->setStatus(ArticleExportQueueStatus::COMPLETED)
            ->setProcessedAt($processedAt);

        $this->assertSame($article, $queueItem->getArticle());
        $this->assertSame($user, $queueItem->getRequestedBy());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertSame('2026-03-22 11:00:00', $queueItem->getProcessedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $queueItem->getProcessedAt()?->getTimezone()->getName());
    }

    public function testNonCompletedStatusClearsProcessedAt(): void
    {
        $queueItem = (new ArticleExportQueue(new Article()))
            ->setProcessedAt(new \DateTimeImmutable('2026-03-22 12:00:00', new \DateTimeZone('Europe/Warsaw')))
            ->setStatus(ArticleExportQueueStatus::FAILED);

        $this->assertNull($queueItem->getProcessedAt());
    }

    public function testLifecycleCallbacksRefreshTimestamps(): void
    {
        $queueItem = new ArticleExportQueue(new Article());
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
