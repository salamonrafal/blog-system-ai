<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleImportQueueCommand;
use App\Entity\ArticleImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessArticleImportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor->expects($this->never())->method('process');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompletedWhenImportSucceeds(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(1);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertNull($queueItem->getErrorMessage());
        $this->assertNotNull($queueItem->getProcessedAt());
        $this->assertStringContainsString('Imported 1 queued article file(s).', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsFailedAndStoresErrorMessage(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willThrowException(new \RuntimeException('Pole title jest wymagane.'));
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $queueItem->getStatus());
        $this->assertSame('Pole title jest wymagane.', $queueItem->getErrorMessage());
        $this->assertNull($queueItem->getProcessedAt());
        $this->assertStringContainsString('Article import failed for queue item', $tester->getDisplay());
    }

    public function testExecuteResetsClosedEntityManagerBeforePersistingFailureState(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $managedQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $this->setEntityId($queueItem, 42);
        $this->setEntityId($managedQueueItem, 42);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database write failed.'));
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);

        $recoveredEntityManager = $this->createMock(EntityManagerInterface::class);
        $recoveredEntityManager
            ->expects($this->once())
            ->method('find')
            ->with(ArticleImportQueue::class, 42)
            ->willReturn($managedQueueItem);
        $recoveredEntityManager
            ->expects($this->once())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('resetManager');
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ArticleImportQueue::class)
            ->willReturn($recoveredEntityManager);

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(1);

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $managedQueueItem->getStatus());
        $this->assertSame('Database write failed.', $managedQueueItem->getErrorMessage());
        $this->assertStringContainsString('Article import failed for queue item 42: Database write failed.', $tester->getDisplay());
    }

    /**
     * @param list<ArticleImportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): ArticleImportQueueRepository
    {
        /** @var ArticleImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleImportQueueRepository::class);
        $repository
            ->expects($this->exactly(\count($queueItems) + 1))
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...[...$queueItems, null]);

        return $repository;
    }

    private function setEntityId(ArticleImportQueue $queueItem, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($queueItem, 'id');
        $reflectionProperty->setValue($queueItem, $id);
    }
}
