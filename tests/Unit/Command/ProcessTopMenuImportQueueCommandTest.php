<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessTopMenuImportQueueCommand;
use App\Entity\TopMenuImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\TopMenuImportQueueRepository;
use App\Service\TopMenuCacheManager;
use App\Service\TopMenuImportProcessor;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessTopMenuImportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $processor = $this->createMock(TopMenuImportProcessor::class);
        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessTopMenuImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $topMenuCacheManager, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued top menu imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompleted(): void
    {
        $queueItem = new TopMenuImportQueue();
        $queueItem->setStatus(ArticleImportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(TopMenuImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(4);
        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager
            ->expects($this->once())
            ->method('refresh');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyTopMenuImportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessTopMenuImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $topMenuCacheManager, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
    }

    /**
     * @param list<TopMenuImportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): TopMenuImportQueueRepository
    {
        /** @var TopMenuImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuImportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }
}
