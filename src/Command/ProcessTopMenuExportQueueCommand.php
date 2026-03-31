<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ArticleExport;
use App\Entity\TopMenuExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\TopMenuExportFileWriter;
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
    name: 'app:top-menu-export:process-queue',
    description: 'Exports the full top menu hierarchy into a restorable file and registers the export.'
)]
class ProcessTopMenuExportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly TopMenuExportQueueRepository $topMenuExportQueueRepository,
        private readonly TopMenuExportFileWriter $topMenuExportFileWriter,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->entityManager;
        $queueRepository = $this->topMenuExportQueueRepository;
        $queueItem = $queueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued top menu exports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            $filePath = null;

            try {
                $filePath = $this->topMenuExportFileWriter->write($queueItem);

                $articleExport = (new ArticleExport())
                    ->setStatus(ArticleExportStatus::NEW)
                    ->setType(ArticleExportType::TOP_MENU)
                    ->setFilePath($filePath)
                    ->setItemsCount($this->topMenuItemRepository->count([]))
                    ->setRequestedBy($queueItem->getRequestedBy());

                $queueItem
                    ->setStatus(ArticleExportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow());

                $entityManager->persist($articleExport);
                $entityManager->flush();
                $this->notifyExportCompletion($queueItem->getRequestedBy()?->getId(), true, $queueItem, $filePath);
                ++$processedCount;
            } catch (\Throwable $exception) {
                if (is_string($filePath)) {
                    $this->deleteWrittenExportFile($filePath, $queueItem);
                }

                $this->logger->error('Top menu export failed while processing queue item.', [
                    'queue_item_id' => $queueItem->getId(),
                    'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                    'file_path' => $filePath,
                    'exception' => $exception,
                ]);

                [$entityManager, $queueRepository] = $this->markQueueItemAsFailed(
                    $queueItem,
                    $entityManager,
                    $queueRepository,
                );
                $this->notifyExportCompletion($queueItem->getRequestedBy()?->getId(), false, $queueItem, $filePath);
                ++$failedCount;

                $io->error(sprintf(
                    'Top menu export failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $queueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Exported %d queued top menu snapshot(s).', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued top menu export(s), but %d export(s) failed.',
            $processedCount,
            $failedCount
        ));

        return Command::FAILURE;
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private function markQueueItemAsFailed(
        TopMenuExportQueue $queueItem,
        EntityManagerInterface $entityManager,
        TopMenuExportQueueRepository $queueRepository,
    ): array {
        if ($entityManager->isOpen()) {
            $queueItem->setStatus(ArticleExportQueueStatus::FAILED);
            $entityManager->flush();

            return [$entityManager, $queueRepository];
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(TopMenuExportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for top menu export queue is not available.');
        }

        $managedQueueItem = $entityManager->find(TopMenuExportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof TopMenuExportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload top menu export queue item %d after export failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem->setStatus(ArticleExportQueueStatus::FAILED);
        $entityManager->flush();

        return [$entityManager, $this->refreshQueueRepository()];
    }

    private function refreshQueueRepository(): TopMenuExportQueueRepository
    {
        $repository = $this->managerRegistry->getRepository(TopMenuExportQueue::class);
        if (!$repository instanceof TopMenuExportQueueRepository) {
            throw new \RuntimeException('Top menu export queue repository is not available.');
        }

        return $repository;
    }

    private function deleteWrittenExportFile(string $filePath, TopMenuExportQueue $queueItem): void
    {
        try {
            $this->topMenuExportFileWriter->delete($filePath);
        } catch (\Throwable $cleanupException) {
            $this->logger->warning('Failed to delete written top menu export file after queue processing error.', [
                'queue_item_id' => $queueItem->getId(),
                'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                'file_path' => $filePath,
                'exception' => $cleanupException,
            ]);
        }
    }

    private function notifyExportCompletion(?int $userId, bool $success, TopMenuExportQueue $queueItem, ?string $filePath): void
    {
        try {
            $this->userNotificationService->notifyExportCompleted($userId, $success);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to create top menu export completion notification.', [
                'queue_item_id' => $queueItem->getId(),
                'requested_by_user_id' => $userId,
                'file_path' => $filePath,
                'success' => $success,
                'exception' => $exception,
            ]);
        }
    }
}
