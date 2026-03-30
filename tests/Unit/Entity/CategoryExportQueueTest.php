<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ArticleCategory;
use App\Entity\CategoryExportQueue;
use App\Entity\User;
use App\Enum\ArticleExportQueueStatus;
use PHPUnit\Framework\TestCase;

final class CategoryExportQueueTest extends TestCase
{
    public function testQueueItemExposesAssignedValues(): void
    {
        $category = (new ArticleCategory())->setName('AI');
        $user = (new User())
            ->setEmail('exporter@example.com')
            ->setFullName('Eksporter');

        $processedAt = new \DateTimeImmutable('2026-03-22 12:00:00', new \DateTimeZone('Europe/Warsaw'));

        $queueItem = (new CategoryExportQueue($category))
            ->setRequestedBy($user)
            ->setStatus(ArticleExportQueueStatus::COMPLETED)
            ->setProcessedAt($processedAt);

        $this->assertSame($category, $queueItem->getCategory());
        $this->assertSame($user, $queueItem->getRequestedBy());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertSame('2026-03-22 11:00:00', $queueItem->getProcessedAt()?->format('Y-m-d H:i:s'));
    }
}
