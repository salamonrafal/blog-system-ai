<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\CategoryImportQueue;
use App\Enum\ArticleImportQueueStatus;
use PHPUnit\Framework\TestCase;

final class CategoryImportQueueTest extends TestCase
{
    public function testQueueItemStoresNormalizedData(): void
    {
        $queueItem = (new CategoryImportQueue())
            ->setOriginalFilename(' category.json ')
            ->setFilePath(' var/imports/category-import-1.json ')
            ->setErrorMessage(' error ')
            ->setStatus(ArticleImportQueueStatus::FAILED);

        $this->assertSame('category.json', $queueItem->getOriginalFilename());
        $this->assertSame('var/imports/category-import-1.json', $queueItem->getFilePath());
        $this->assertSame('error', $queueItem->getErrorMessage());
        $this->assertSame(ArticleImportQueueStatus::FAILED, $queueItem->getStatus());
        $this->assertNull($queueItem->getProcessedAt());
    }
}
