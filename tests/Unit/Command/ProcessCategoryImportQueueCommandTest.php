<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessCategoryImportQueueCommand;
use App\Entity\CategoryImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\CategoryImportQueueRepository;
use App\Service\CategoryImportProcessor;
use App\Service\UserNotificationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessCategoryImportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('getConnection');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $processor = $this->createMock(CategoryImportProcessor::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessCategoryImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued category imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompleted(): void
    {
        $queueItem = new CategoryImportQueue();
        $queueItem->setStatus(ArticleImportQueueStatus::PROCESSING);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(CategoryImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(2);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyCategoryImportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessCategoryImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
    }

    private function createQueueRepositoryMock(array $queueItems): CategoryImportQueueRepository
    {
        /** @var CategoryImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(CategoryImportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }
}
