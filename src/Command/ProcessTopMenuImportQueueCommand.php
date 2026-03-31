<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\TopMenuImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\TopMenuImportQueueRepository;
use App\Service\TopMenuCacheManager;
use App\Service\TopMenuImportProcessor;
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
    name: 'app:top-menu-import:process-queue',
    description: 'Imports queued top menu export files and creates or updates the hierarchy.'
)]
class ProcessTopMenuImportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly TopMenuImportQueueRepository $topMenuImportQueueRepository,
        private readonly TopMenuImportProcessor $topMenuImportProcessor,
        private readonly TopMenuCacheManager $topMenuCacheManager,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->entityManager;
        $queueRepository = $this->topMenuImportQueueRepository;
        $queueItem = $queueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued top menu imports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            try {
                $importedItems = $this->topMenuImportProcessor->process($queueItem);

                $queueItem
                    ->setStatus(ArticleImportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow())
                    ->setErrorMessage(null);

                $entityManager->flush();
                $this->topMenuCacheManager->refresh();
                $this->notifyImportCompletion($queueItem->getRequestedBy()?->getId(), true, $queueItem);
                ++$processedCount;

                $io->success(sprintf(
                    'Imported %d top menu item(s) from queue item %d.',
                    $importedItems,
                    $queueItem->getId() ?? 0,
                ));
            } catch (\Throwable $exception) {
                $this->logger->error('Top menu import failed while processing queue item.', [
                    'queue_item_id' => $queueItem->getId(),
                    'file_path' => $queueItem->getFilePath(),
                    'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                    'exception' => $exception,
                ]);

                [$entityManager, $queueRepository] = $this->markQueueItemAsFailed(
                    $queueItem,
                    $exception->getMessage(),
                    $entityManager,
                    $queueRepository,
                );
                $this->notifyImportCompletion($queueItem->getRequestedBy()?->getId(), false, $queueItem);
                ++$failedCount;

                $io->error(sprintf(
                    'Top menu import failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $queueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Imported %d queued top menu file(s).', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued top menu file(s), but %d import(s) failed.',
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
        TopMenuImportQueue $queueItem,
        string $errorMessage,
        EntityManagerInterface $entityManager,
        TopMenuImportQueueRepository $queueRepository,
    ): array {
        if ($entityManager->isOpen()) {
            $queueItem
                ->setStatus(ArticleImportQueueStatus::FAILED)
                ->setErrorMessage($errorMessage);

            $entityManager->flush();

            return [$entityManager, $queueRepository];
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(TopMenuImportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for top menu import queue is not available.');
        }

        $managedQueueItem = $entityManager->find(TopMenuImportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof TopMenuImportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload top menu import queue item %d after import failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem
            ->setStatus(ArticleImportQueueStatus::FAILED)
            ->setErrorMessage($errorMessage);

        $entityManager->flush();

        return [$entityManager, $this->refreshQueueRepository()];
    }

    private function refreshQueueRepository(): TopMenuImportQueueRepository
    {
        $repository = $this->managerRegistry->getRepository(TopMenuImportQueue::class);
        if (!$repository instanceof TopMenuImportQueueRepository) {
            throw new \RuntimeException('Top menu import queue repository is not available.');
        }

        return $repository;
    }

    private function notifyImportCompletion(?int $userId, bool $success, TopMenuImportQueue $queueItem): void
    {
        try {
            $this->userNotificationService->notifyTopMenuImportCompleted($userId, $success);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to create top menu import completion notification.', [
                'queue_item_id' => $queueItem->getId(),
                'file_path' => $queueItem->getFilePath(),
                'requested_by_user_id' => $userId,
                'success' => $success,
                'exception' => $exception,
            ]);
        }
    }
}
