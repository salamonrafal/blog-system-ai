<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessCategoryExportQueueCommand;
use App\Entity\ArticleExport;
use App\Entity\ArticleCategory;
use App\Entity\CategoryExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\CategoryExportQueueRepository;
use App\Service\CategoryExportFileWriter;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessCategoryExportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('persist');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $writer = $this->createMock(CategoryExportFileWriter::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessCategoryExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued category exports to process.', $tester->getDisplay());
    }

    public function testExecuteCreatesCategoryExportAndMarksQueueItemAsCompleted(): void
    {
        $capturedExports = [];
        $queueItem = new CategoryExportQueue((new ArticleCategory())->setName('AI'));
        $queueItem->setStatus(ArticleExportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleExport $articleExport) use (&$capturedExports): void {
                $capturedExports[] = $articleExport;
            });
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $writer = $this->createMock(CategoryExportFileWriter::class);
        $writer
            ->expects($this->once())
            ->method('write')
            ->with($queueItem)
            ->willReturn('var/exports/category-1-export.json');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyExportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessCategoryExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(1, $capturedExports);
        $this->assertSame(ArticleExportStatus::NEW, $capturedExports[0]->getStatus());
        $this->assertSame(ArticleExportType::CATEGORIES, $capturedExports[0]->getType());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItem->getStatus());
    }

    /**
     * @param list<CategoryExportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): CategoryExportQueueRepository
    {
        /** @var CategoryExportQueueRepository&MockObject $repository */
        $repository = $this->createMock(CategoryExportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }
}
