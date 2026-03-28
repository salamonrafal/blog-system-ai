<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleImportQueueCommand;
use App\Entity\ArticleImportQueue;
use App\Entity\User;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportProcessor;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService->expects($this->never())->method('notifyImportCompleted');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $userNotificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompletedWhenImportSucceeds(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setRequestedBy(
                (new User())
                    ->setEmail('importer@example.com')
                    ->setFullName('Importer')
            )
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
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->once())
            ->method('notifyImportCompleted')
            ->with($queueItem->getRequestedBy()?->getId(), true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $userNotificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $queueItem->getStatus());
        $this->assertNull($queueItem->getErrorMessage());
        $this->assertNotNull($queueItem->getProcessedAt());
        $this->assertStringContainsString('Imported 1 queued article file(s).', $tester->getDisplay());
    }

    public function testExecuteKeepsCompletedImportWhenNotificationCreationFails(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $this->setEntityId($queueItem, 42);

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

        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->once())
            ->method('notifyImportCompleted')
            ->with(null, true)
            ->willThrowException(new \RuntimeException('Notification storage failed.'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed to create import completion notification.',
                $this->callback(static function (array $context): bool {
                    return 42 === $context['queue_item_id']
                        && false === $context['success'] ? false : true;
                })
            );

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $userNotificationService, $logger));
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
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->once())
            ->method('notifyImportCompleted')
            ->with(null, false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Article import failed while processing queue item.',
                $this->callback(static function (array $context): bool {
                    return array_key_exists('requested_by_user_id', $context);
                })
            );

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $userNotificationService, $logger));
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

        $refreshedQueueRepository = $this->createQueueRepositoryMock([]);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('resetManager');
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ArticleImportQueue::class)
            ->willReturn($recoveredEntityManager);
        $managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with(ArticleImportQueue::class)
            ->willReturn($refreshedQueueRepository);

        $queueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('claimNextPending')
            ->willReturn($queueItem);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(1);
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->once())
            ->method('notifyImportCompleted')
            ->with(null, false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $queueRepository, $processor, $userNotificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $managedQueueItem->getStatus());
        $this->assertSame('Database write failed.', $managedQueueItem->getErrorMessage());
        $this->assertStringContainsString('Article import failed for queue item 42: Database write failed.', $tester->getDisplay());
    }

    public function testExecuteContinuesWithFreshRepositoryAfterResettingClosedEntityManager(): void
    {
        $failedQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article-1.json')
            ->setFilePath('var/imports/article-1.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $managedFailedQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article-1.json')
            ->setFilePath('var/imports/article-1.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $successfulQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article-2.json')
            ->setFilePath('var/imports/article-2.json')
            ->setStatus(ArticleImportQueueStatus::PROCESSING);
        $this->setEntityId($failedQueueItem, 42);
        $this->setEntityId($managedFailedQueueItem, 42);
        $this->setEntityId($successfulQueueItem, 43);

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
            ->willReturn($managedFailedQueueItem);
        $recoveredEntityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $staleQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $staleQueueRepository
            ->expects($this->once())
            ->method('claimNextPending')
            ->willReturn($failedQueueItem);

        $freshQueueRepository = $this->createMock(ArticleImportQueueRepository::class);
        $freshQueueRepository
            ->expects($this->exactly(2))
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls($successfulQueueItem, null);

        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->exactly(2))
            ->method('process')
            ->willReturnCallback(static function (ArticleImportQueue $queueItem): int {
                if (42 === $queueItem->getId()) {
                    return 1;
                }

                return 2;
            });

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('resetManager');
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(ArticleImportQueue::class)
            ->willReturn($recoveredEntityManager);
        $managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with(ArticleImportQueue::class)
            ->willReturn($freshQueueRepository);
        $userNotificationService = $this->createMock(UserNotificationService::class);
        $userNotificationService
            ->expects($this->exactly(2))
            ->method('notifyImportCompleted')
            ->willReturnCallback(static function (?int $userId, bool $success): void {
                TestCase::assertContains([$userId, $success], [[null, false], [null, true]]);
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $managerRegistry, $staleQueueRepository, $processor, $userNotificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $managedFailedQueueItem->getStatus());
        $this->assertSame('Database write failed.', $managedFailedQueueItem->getErrorMessage());
        $this->assertSame(ArticleImportQueueStatus::COMPLETED, $successfulQueueItem->getStatus());
        $this->assertNull($successfulQueueItem->getErrorMessage());
        $this->assertNotNull($successfulQueueItem->getProcessedAt());
        $this->assertStringContainsString('Article import failed for queue item 42: Database write failed.', $tester->getDisplay());
        $this->assertStringContainsString('Imported 2 article(s) from queue item 43.', $tester->getDisplay());
        $this->assertStringContainsString('Processed 1 queued article file(s), but 1 import(s) failed.', $tester->getDisplay());
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
