<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleExportQueueCommand;
use App\Entity\Article;
use App\Entity\ArticleExport;
use App\Entity\ArticleExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\ArticleExportQueueRepository;
use App\Service\ArticleExportFileWriter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessArticleExportQueueCommandTest extends TestCase
{
    public function testExecuteReturnsSuccessWhenQueueIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('isOpen');

        $queueRepository = $this->createQueueRepositoryMock([]);
        $writer = $this->createMock(ArticleExportFileWriter::class);
        $writer->expects($this->never())->method('write');
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article exports to process.', $tester->getDisplay());
    }

    public function testExecuteCreatesSeparateExportsAndMarksQueueItemsAsCompleted(): void
    {
        $capturedExports = [];
        $queueItemOne = new ArticleExportQueue((new Article())->setTitle('Artykul 1')->setSlug('artykul-1'));
        $queueItemTwo = new ArticleExportQueue((new Article())->setTitle('Artykul 2')->setSlug('artykul-2'));
        $queueItemOne->setStatus(ArticleExportQueueStatus::PROCESSING);
        $queueItemTwo->setStatus(ArticleExportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (ArticleExport $articleExport) use (&$capturedExports): void {
                $capturedExports[] = $articleExport;
            });
        $entityManager->expects($this->exactly(2))->method('flush');
        $entityManager->expects($this->never())->method('isOpen');

        $queueRepository = $this->createQueueRepositoryMock([$queueItemOne, $queueItemTwo]);
        $writer = $this->createMock(ArticleExportFileWriter::class);
        $writer
            ->expects($this->exactly(2))
            ->method('write')
            ->willReturnMap([
                [$queueItemOne, 'var/exports/article-1-export.json'],
                [$queueItemTwo, 'var/exports/article-2-export.json'],
            ]);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(2, $capturedExports);
        $this->assertSame(ArticleExportStatus::NEW, $capturedExports[0]->getStatus());
        $this->assertSame(ArticleExportType::ARTICLES, $capturedExports[0]->getType());
        $this->assertSame('var/exports/article-1-export.json', $capturedExports[0]->getFilePath());
        $this->assertSame(1, $capturedExports[0]->getArticleCount());
        $this->assertSame('var/exports/article-2-export.json', $capturedExports[1]->getFilePath());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItemOne->getStatus());
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItemTwo->getStatus());
        $this->assertNotNull($queueItemOne->getProcessedAt());
        $this->assertNotNull($queueItemTwo->getProcessedAt());
        $this->assertStringContainsString('Exported 2 queued article(s) into separate files.', $tester->getDisplay());
    }

    public function testExecuteMarksOnlyFailedQueueItemWhenWriterThrowsException(): void
    {
        $capturedExports = [];
        $queueItemOne = new ArticleExportQueue((new Article())->setTitle('Artykul 1')->setSlug('artykul-1'));
        $queueItemTwo = new ArticleExportQueue((new Article())->setTitle('Artykul 2')->setSlug('artykul-2'));
        $queueItemOne->setStatus(ArticleExportQueueStatus::PROCESSING);
        $queueItemTwo->setStatus(ArticleExportQueueStatus::PROCESSING);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleExport $articleExport) use (&$capturedExports): void {
                $capturedExports[] = $articleExport;
            });
        $entityManager->expects($this->exactly(2))->method('flush');
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);

        $queueRepository = $this->createQueueRepositoryMock([$queueItemOne, $queueItemTwo]);
        $writer = $this->createMock(ArticleExportFileWriter::class);
        $writer
            ->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(static function (ArticleExportQueue $queueItem): string {
                if ('artykul-1' === $queueItem->getArticle()->getSlug()) {
                    return 'var/exports/article-1-export.json';
                }

                throw new \RuntimeException('Disk is full');
            });
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->never())->method('resetManager');

        $tester = new CommandTester(new ProcessArticleExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertCount(1, $capturedExports);
        $this->assertSame(ArticleExportQueueStatus::COMPLETED, $queueItemOne->getStatus());
        $this->assertNotNull($queueItemOne->getProcessedAt());
        $this->assertSame(ArticleExportQueueStatus::FAILED, $queueItemTwo->getStatus());
        $this->assertNull($queueItemTwo->getProcessedAt());
        $this->assertStringContainsString('Article export failed for queue item', $tester->getDisplay());
        $this->assertStringContainsString('Processed 1 queued article(s), but 1 export(s) failed.', $tester->getDisplay());
    }

    public function testExecuteResetsClosedEntityManagerBeforePersistingFailureState(): void
    {
        $queueItem = new ArticleExportQueue((new Article())->setTitle('Artykul 1')->setSlug('artykul-1'));
        $managedQueueItem = new ArticleExportQueue((new Article())->setTitle('Artykul 1')->setSlug('artykul-1'));
        $queueItem->setStatus(ArticleExportQueueStatus::PROCESSING);
        $managedQueueItem->setStatus(ArticleExportQueueStatus::PROCESSING);
        $this->setEntityId($queueItem, 42);
        $this->setEntityId($managedQueueItem, 42);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist');
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
            ->with(ArticleExportQueue::class, 42)
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
            ->with(ArticleExportQueue::class)
            ->willReturn($recoveredEntityManager);

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $writer = $this->createMock(ArticleExportFileWriter::class);
        $writer
            ->expects($this->once())
            ->method('write')
            ->with($queueItem)
            ->willReturn('var/exports/article-1-export.json');

        $tester = new CommandTester(new ProcessArticleExportQueueCommand($entityManager, $managerRegistry, $queueRepository, $writer));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleExportQueueStatus::FAILED, $managedQueueItem->getStatus());
        $this->assertStringContainsString('Article export failed for queue item 42: Database write failed.', $tester->getDisplay());
    }

    /**
     * @param list<ArticleExportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): ArticleExportQueueRepository
    {
        /** @var ArticleExportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleExportQueueRepository::class);
        $repository
            ->expects($this->exactly(\count($queueItems) + 1))
            ->method('claimNextPending')
            ->willReturnOnConsecutiveCalls(...[...$queueItems, null]);

        return $repository;
    }

    private function setEntityId(ArticleExportQueue $queueItem, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($queueItem, 'id');
        $reflectionProperty->setValue($queueItem, $id);
    }
}
