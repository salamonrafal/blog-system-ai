<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ArticleExport;
use App\Entity\ArticleExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\ArticleExportQueueRepository;
use App\Service\ArticleExportFileWriter;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:article-export:process-queue',
    description: 'Exports queued articles into a restorable file and registers the export.'
)]
class ProcessArticleExportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ArticleExportQueueRepository $articleExportQueueRepository,
        private readonly ArticleExportFileWriter $articleExportFileWriter,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueItem = $this->articleExportQueueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued article exports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            $filePath = null;

            try {
                $filePath = $this->articleExportFileWriter->write($queueItem);

                $articleExport = (new ArticleExport())
                    ->setStatus(ArticleExportStatus::NEW)
                    ->setType(ArticleExportType::ARTICLES)
                    ->setFilePath($filePath)
                    ->setArticleCount(1)
                    ->setRequestedBy($queueItem->getRequestedBy());

                $queueItem
                    ->setStatus(ArticleExportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow());

                $this->entityManager->persist($articleExport);
                $this->entityManager->flush();
                $this->userNotificationService->notifyExportCompleted($queueItem->getRequestedBy()?->getId(), true);
                ++$processedCount;
            } catch (\Throwable $exception) {
                if (is_string($filePath)) {
                    $this->deleteWrittenExportFile($filePath, $queueItem);
                }

                $this->logger->error('Article export failed while processing queue item.', [
                    'queue_item_id' => $queueItem->getId(),
                    'article_id' => $queueItem->getArticle()->getId(),
                    'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                    'file_path' => $filePath,
                    'exception' => $exception,
                ]);

                $this->markQueueItemAsFailed($queueItem);
                $this->userNotificationService->notifyExportCompleted($queueItem->getRequestedBy()?->getId(), false);
                ++$failedCount;

                $io->error(sprintf(
                    'Article export failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $this->articleExportQueueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Exported %d queued article(s) into separate files.', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued article(s), but %d export(s) failed.',
            $processedCount,
            $failedCount
        ));

        return Command::FAILURE;
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private function markQueueItemAsFailed(ArticleExportQueue $queueItem): void
    {
        if ($this->entityManager->isOpen()) {
            $queueItem->setStatus(ArticleExportQueueStatus::FAILED);

            $this->entityManager->flush();

            return;
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(ArticleExportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for article export queue is not available.');
        }

        $managedQueueItem = $entityManager->find(ArticleExportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof ArticleExportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload article export queue item %d after export failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem->setStatus(ArticleExportQueueStatus::FAILED);

        $entityManager->flush();
    }

    private function deleteWrittenExportFile(string $filePath, ArticleExportQueue $queueItem): void
    {
        try {
            $this->articleExportFileWriter->delete($filePath);
        } catch (\Throwable $cleanupException) {
            $this->logger->warning('Failed to delete written export file after queue processing error.', [
                'queue_item_id' => $queueItem->getId(),
                'article_id' => $queueItem->getArticle()->getId(),
                'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                'file_path' => $filePath,
                'exception' => $cleanupException,
            ]);
        }
    }
}
