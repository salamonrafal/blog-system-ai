<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleKeywordImportQueueCommand;
use App\Entity\ArticleKeywordImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleKeywordImportQueueRepository;
use App\Service\ArticleKeywordImportProcessor;
use App\Service\UserNotificationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessArticleKeywordImportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('getConnection');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $processor = $this->createMock(ArticleKeywordImportProcessor::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $tester = new CommandTester(new ProcessArticleKeywordImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article keyword imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompleted(): void
    {
        $queueItem = new ArticleKeywordImportQueue();
        $queueItem->setStatus(ArticleImportQueueStatus::PROCESSING);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleKeywordImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(2);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyKeywordImportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessArticleKeywordImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertStringContainsString('Imported 2 keywords from queue item 0.', $tester->getDisplay());
        $this->assertStringContainsString('Imported 1 queued article keyword file(s).', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsFailedAndStoresErrorMessage(): void
    {
        $queueItem = new ArticleKeywordImportQueue();
        $queueItem->setStatus(ArticleImportQueueStatus::PROCESSING);
        $this->setEntityId($queueItem, 21);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('isTransactionActive')->willReturn(true);
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('getConnection')->willReturn($connection);
        $entityManager->expects($this->once())->method('clear');
        $entityManager->expects($this->exactly(2))->method('isOpen')->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ArticleKeywordImportQueue::class, 21)
            ->willReturn($queueItem);
        $entityManager->expects($this->once())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleKeywordImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willThrowException(new \RuntimeException('Field keywords[0].name is required.'));
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyKeywordImportCompleted')->with(null, false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $tester = new CommandTester(new ProcessArticleKeywordImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $queueItem->getStatus());
        $this->assertSame('Field keywords[0].name is required.', $queueItem->getErrorMessage());
        $this->assertStringContainsString('Article keyword import failed for queue item', $tester->getDisplay());
    }

    /**
     * @param list<ArticleKeywordImportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): ArticleKeywordImportQueueRepository
    {
        /** @var ArticleKeywordImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleKeywordImportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}
