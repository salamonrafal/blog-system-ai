<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ProcessArticleImportQueueCommand;
use App\Entity\ArticleImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportProcessor;
use Doctrine\ORM\EntityManagerInterface;
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

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No queued article imports to process.', $tester->getDisplay());
    }

    public function testExecuteMarksQueueItemAsCompletedWhenImportSucceeds(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('article.json')
            ->setFilePath('var/imports/article.json');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willReturn(1);

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $queueRepository, $processor));
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
            ->setFilePath('var/imports/article.json');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))->method('flush');

        $queueRepository = $this->createQueueRepositoryMock([$queueItem]);
        $processor = $this->createMock(ArticleImportProcessor::class);
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($queueItem)
            ->willThrowException(new \RuntimeException('Pole title jest wymagane.'));

        $tester = new CommandTester(new ProcessArticleImportQueueCommand($entityManager, $queueRepository, $processor));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(ArticleImportQueueStatus::FAILED, $queueItem->getStatus());
        $this->assertSame('Pole title jest wymagane.', $queueItem->getErrorMessage());
        $this->assertNull($queueItem->getProcessedAt());
        $this->assertStringContainsString('Article import failed for queue item', $tester->getDisplay());
    }

    /**
     * @param list<ArticleImportQueue> $queueItems
     */
    private function createQueueRepositoryMock(array $queueItems): ArticleImportQueueRepository
    {
        /** @var ArticleImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleImportQueueRepository::class);
        $repository
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn($queueItems);

        return $repository;
    }
}
