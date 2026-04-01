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
use Doctrine\DBAL\Connection;
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
        $entityManager->expects($this->never())->method('getConnection');

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

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getConnection')->willReturn($connection);
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

    public function testExecuteRefreshesTopMenuCacheOnlyOnceAfterMultipleSuccessfulImports(): void
    {
        $firstQueueItem = (new TopMenuImportQueue())->setStatus(ArticleImportQueueStatus::PROCESSING);
        $secondQueueItem = (new TopMenuImportQueue())->setStatus(ArticleImportQueueStatus::PROCESSING);

        $firstConnection = $this->createMock(Connection::class);
        $firstConnection->expects($this->once())->method('beginTransaction');
        $firstConnection->expects($this->once())->method('commit');
        $firstConnection->expects($this->never())->method('rollBack');

        $secondConnection = $this->createMock(Connection::class);
        $secondConnection->expects($this->once())->method('beginTransaction');
        $secondConnection->expects($this->once())->method('commit');
        $secondConnection->expects($this->never())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturnOnConsecutiveCalls($firstConnection, $secondConnection);
        $entityManager->expects($this->exactly(2))->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$firstQueueItem, $secondQueueItem]);
        $processor = $this->createMock(TopMenuImportProcessor::class);
        $processor
            ->expects($this->exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(2, 3);
        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager
            ->expects($this->once())
            ->method('refresh');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService
            ->expects($this->exactly(2))
            ->method('notifyTopMenuImportCompleted')
            ->withAnyParameters();
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessTopMenuImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $topMenuCacheManager, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $firstQueueItem->getStatus());
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $secondQueueItem->getStatus());
    }

    public function testExecuteRollsBackTransactionAndReloadsQueueItemBeforeMarkingFailure(): void
    {
        $queueItem = (new TopMenuImportQueue())
            ->setOriginalFilename('top-menu.json')
            ->setFilePath('var/imports/top-menu.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $managedQueueItem = (new TopMenuImportQueue())
            ->setOriginalFilename('top-menu.json')
            ->setFilePath('var/imports/top-menu.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $this->setEntityId($queueItem, 42);
        $this->setEntityId($managedQueueItem, 42);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->never())->method('commit');
        $connection->expects($this->once())->method('isTransactionActive')->willReturn(true);
        $connection->expects($this->once())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->exactly(2))->method('isOpen')->willReturn(true);
        $entityManager->expects($this->once())->method('clear');
        $entityManager->expects($this->once())->method('find')->with(TopMenuImportQueue::class, 42)->willReturn($managedQueueItem);
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(TopMenuImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willThrowException(new \RuntimeException('Import failed.'));

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->never())->method('refresh');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyTopMenuImportCompleted')->with(null, false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $tester = new CommandTester(new ProcessTopMenuImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $topMenuCacheManager, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $managedQueueItem->getStatus());
        $this->assertSame('Import failed.', $managedQueueItem->getErrorMessage());
    }

    private function setEntityId(TopMenuImportQueue $queueItem, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($queueItem, 'id');
        $reflectionProperty->setValue($queueItem, $id);
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
