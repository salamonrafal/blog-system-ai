<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleKeywordExportQueueCommand;
use App\Entity\ArticleExport;
use App\Entity\ArticleKeywordExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Service\ArticleKeywordExportFileWriter;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessArticleKeywordExportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('persist');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $writer = $this->createMock(ArticleKeywordExportFileWriter::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessArticleKeywordExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article keyword exports to process.', $tester->getDisplay());
    }

    public function testExecuteCreatesKeywordExportAndMarksQueueItemAsCompleted(): void
    {
        $capturedExports = [];
        $queueItem = new ArticleKeywordExportQueue();
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
        $writer = $this->createMock(ArticleKeywordExportFileWriter::class);
        $writer
            ->expects($this->once())
            ->method('write')
            ->with($queueItem)
            ->willReturn([
                'file_path' => 'var/exports/article-keywords-export.json',
                'items_count' => 3,
            ]);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $notificationService = $this->createMock(UserNotificationService::class);
        $notificationService->expects($this->once())->method('notifyExportCompleted')->with(null, true);
        $logger = $this->createMock(LoggerInterface::class);

        $tester = new CommandTester(new ProcessArticleKeywordExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer, $notificationService, $logger));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(1, $capturedExports);
        $this->assertSame(ArticleExportStatus::NEW, $capturedExports[0]->getStatus());
        $this->assertSame(ArticleExportType::KEYWORDS, $capturedExports[0]->getType());
        $this->assertSame(3, $capturedExports[0]->getItemsCount());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItem->getStatus());
    }

    /**
     * @param list<ArticleKeywordExportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): ArticleKeywordExportQueueRepository
    {
        /** @var ArticleKeywordExportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleKeywordExportQueueRepository::class);
        $repository
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...array_merge($queueItems, [null]));

        return $repository;
    }
}
