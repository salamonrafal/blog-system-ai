<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessTopMenuExportQueueCommand;
use App\Entity\ArticleExport;
use App\Entity\TopMenuExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\TopMenuExportQueueRepository;
use App\Repository\TopMenuItemRepository;
use App\Service\TopMenuExportFileWriter;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessTopMenuExportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('persist');

        $topMenuItemRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuItemRepository->expects($this->never())->method('count');
        $queueRepository = $this->createQueueRepositoryMock([]);
        $writer = $this->createMock(TopMenuExportFileWriter::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessTopMenuExportQueueCommand($entityManager, $managerRegistry, $topMenuItemRepository, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued top menu exports to process.', $tester->getDisplay());
    }

    public function testExecuteCreatesTopMenuExportAndMarksQueueItemAsCompleted(): void
    {
        $capturedExports = [];
        $queueItem = new TopMenuExportQueue();
        $queueItem->setStatus(ArticleExportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleExport $articleExport) use (&$capturedExports): void {
                $capturedExports[] = $articleExport;
            });
        $entityManager->expects($this->once())->method('flush');

        $topMenuItemRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuItemRepository
            ->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(7);

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $writer = $this->createMock(TopMenuExportFileWriter::class);
        $writer
            ->expects($this->once())
            ->method('write')
            ->with($queueItem)
            ->willReturn('var/exports/top-menu-export.json');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyExportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessTopMenuExportQueueCommand($entityManager, $managerRegistry, $topMenuItemRepository, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(1, $capturedExports);
        $this->assertSame(ArticleExportStatus::NEW, $capturedExports[0]->getStatus());
        $this->assertSame(ArticleExportType::TOP_MENU, $capturedExports[0]->getType());
        $this->assertSame(7, $capturedExports[0]->getItemsCount());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItem->getStatus());
    }

    /**
     * @param list<TopMenuExportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): TopMenuExportQueueRepository
    {
        /** @var TopMenuExportQueueRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuExportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }
}
