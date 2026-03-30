<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ArticleExport;
use App\Entity\CategoryExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\CategoryExportQueueRepository;
use App\Service\CategoryExportFileWriter;
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
    name: 'app:category-export:process-queue',
    description: 'Exports queued categories into a restorable file and registers the export.'
)]
class ProcessCategoryExportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly CategoryExportQueueRepository $categoryExportQueueRepository,
        private readonly CategoryExportFileWriter $categoryExportFileWriter,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueItem = $this->categoryExportQueueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued category exports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            $filePath = null;

            try {
                $filePath = $this->categoryExportFileWriter->write($queueItem);

                $articleExport = (new ArticleExport())
                    ->setStatus(ArticleExportStatus::NEW)
                    ->setType(ArticleExportType::CATEGORIES)
                    ->setFilePath($filePath)
                    ->setArticleCount(1)
                    ->setRequestedBy($queueItem->getRequestedBy());

                $queueItem
                    ->setStatus(ArticleExportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow());

                $this->entityManager->persist($articleExport);
                $this->entityManager->flush();
                $this->notifyExportCompletion($queueItem->getRequestedBy()?->getId(), true, $queueItem, $filePath);
                ++$processedCount;
            } catch (\Throwable $exception) {
                if (is_string($filePath)) {
                    $this->deleteWrittenExportFile($filePath, $queueItem);
                }

                $this->logger->error('Category export failed while processing queue item.', [
                    'queue_item_id' => $queueItem->getId(),
                    'category_id' => $queueItem->getCategory()->getId(),
                    'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                    'file_path' => $filePath,
                    'exception' => $exception,
                ]);

                $this->markQueueItemAsFailed($queueItem);
                $this->notifyExportCompletion($queueItem->getRequestedBy()?->getId(), false, $queueItem, $filePath);
                ++$failedCount;

                $io->error(sprintf(
                    'Category export failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $this->categoryExportQueueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Exported %d queued category(s) into separate files.', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued category(s), but %d export(s) failed.',
            $processedCount,
            $failedCount
        ));

        return Command::FAILURE;
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private function markQueueItemAsFailed(CategoryExportQueue $queueItem): void
    {
        if ($this->entityManager->isOpen()) {
            $queueItem->setStatus(ArticleExportQueueStatus::FAILED);
            $this->entityManager->flush();

            return;
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(CategoryExportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for category export queue is not available.');
        }

        $managedQueueItem = $entityManager->find(CategoryExportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof CategoryExportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload category export queue item %d after export failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem->setStatus(ArticleExportQueueStatus::FAILED);
        $entityManager->flush();
    }

    private function deleteWrittenExportFile(string $filePath, CategoryExportQueue $queueItem): void
    {
        try {
            $this->categoryExportFileWriter->delete($filePath);
        } catch (\Throwable $cleanupException) {
            $this->logger->warning('Failed to delete written export file after queue processing error.', [
                'queue_item_id' => $queueItem->getId(),
                'category_id' => $queueItem->getCategory()->getId(),
                'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                'file_path' => $filePath,
                'exception' => $cleanupException,
            ]);
        }
    }

    private function notifyExportCompletion(?int $userId, bool $success, CategoryExportQueue $queueItem, ?string $filePath): void
    {
        try {
            $this->userNotificationService->notifyExportCompleted($userId, $success);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to create export completion notification.', [
                'queue_item_id' => $queueItem->getId(),
                'category_id' => $queueItem->getCategory()->getId(),
                'requested_by_user_id' => $userId,
                'file_path' => $filePath,
                'success' => $success,
                'exception' => $exception,
            ]);
        }
    }
}
