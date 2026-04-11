<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ArticleKeywordImportQueue;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleKeywordImportQueueRepository;
use App\Service\ArticleKeywordImportProcessor;
use App\Service\UserNotificationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:article-keyword-import:process-queue',
    description: 'Imports queued article keyword export files and creates or updates keywords by language and name.'
)]
class ProcessArticleKeywordImportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ArticleKeywordImportQueueRepository $articleKeywordImportQueueRepository,
        private readonly ArticleKeywordImportProcessor $articleKeywordImportProcessor,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->entityManager;
        $queueRepository = $this->articleKeywordImportQueueRepository;
        $queueItem = $queueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued article keyword imports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            $connection = $entityManager->getConnection();

            try {
                $connection->beginTransaction();
                $importedItems = $this->articleKeywordImportProcessor->process($queueItem);

                $queueItem
                    ->setStatus(ArticleImportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow())
                    ->setErrorMessage(null);

                $entityManager->flush();
                $connection->commit();
                $this->notifyImportCompletion($queueItem->getRequestedBy()?->getId(), true, $queueItem);
                ++$processedCount;

                $io->success(sprintf(
                    'Imported %d keyword%s from queue item %d.',
                    $importedItems,
                    1 === $importedItems ? '' : 's',
                    $queueItem->getId() ?? 0,
                ));
            } catch (\Throwable $exception) {
                $this->logger->error('Article keyword import failed while processing queue item.', [
                    'queue_item_id' => $queueItem->getId(),
                    'file_path' => $queueItem->getFilePath(),
                    'requested_by_user_id' => $queueItem->getRequestedBy()?->getId(),
                    'exception' => $exception,
                ]);
                $this->rollbackFailedImportTransaction($entityManager, $connection);

                [$entityManager, $queueRepository] = $this->markQueueItemAsFailed(
                    $queueItem,
                    $exception->getMessage(),
                    $entityManager,
                    $queueRepository,
                );
                $this->notifyImportCompletion($queueItem->getRequestedBy()?->getId(), false, $queueItem);
                ++$failedCount;

                $io->error(sprintf(
                    'Article keyword import failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $queueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Imported %d queued article keyword file(s).', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued article keyword file(s), but %d import(s) failed.',
            $processedCount,
            $failedCount
        ));

        return Command::FAILURE;
    }

    private function rollbackFailedImportTransaction(EntityManagerInterface $entityManager, Connection $connection): void
    {
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        if ($entityManager->isOpen()) {
            $entityManager->clear();
        }
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    private function markQueueItemAsFailed(
        ArticleKeywordImportQueue $queueItem,
        string $errorMessage,
        EntityManagerInterface $entityManager,
        ArticleKeywordImportQueueRepository $queueRepository,
    ): array {
        if ($entityManager->isOpen()) {
            $managedQueueItem = $entityManager->find(ArticleKeywordImportQueue::class, $queueItem->getId());
            if (!$managedQueueItem instanceof ArticleKeywordImportQueue) {
                throw new \RuntimeException(sprintf(
                    'Unable to reload article keyword import queue item %d after import failure.',
                    $queueItem->getId() ?? 0,
                ));
            }

            $managedQueueItem
                ->setStatus(ArticleImportQueueStatus::FAILED)
                ->setErrorMessage($errorMessage);

            $entityManager->flush();

            return [$entityManager, $queueRepository];
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(ArticleKeywordImportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for article keyword import queue is not available.');
        }

        $managedQueueItem = $entityManager->find(ArticleKeywordImportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof ArticleKeywordImportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload article keyword import queue item %d after import failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem
            ->setStatus(ArticleImportQueueStatus::FAILED)
            ->setErrorMessage($errorMessage);

        $entityManager->flush();

        return [$entityManager, $this->refreshQueueRepository()];
    }

    private function refreshQueueRepository(): ArticleKeywordImportQueueRepository
    {
        $repository = $this->managerRegistry->getRepository(ArticleKeywordImportQueue::class);
        if (!$repository instanceof ArticleKeywordImportQueueRepository) {
            throw new \RuntimeException('Article keyword import queue repository is not available.');
        }

        return $repository;
    }

    private function notifyImportCompletion(?int $userId, bool $success, ArticleKeywordImportQueue $queueItem): void
    {
        try {
            $this->userNotificationService->notifyKeywordImportCompleted($userId, $success);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to create article keyword import completion notification.', [
                'queue_item_id' => $queueItem->getId(),
                'file_path' => $queueItem->getFilePath(),
                'requested_by_user_id' => $userId,
                'success' => $success,
                'exception' => $exception,
            ]);
        }
    }
}
