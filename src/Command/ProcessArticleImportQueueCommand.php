<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportProcessor;
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
    name: 'app:article-import:process-queue',
    description: 'Imports queued article export files and creates or updates articles.'
)]
class ProcessArticleImportQueueCommand extends Command
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ArticleImportQueueRepository $articleImportQueueRepository,
        private readonly ArticleImportProcessor $articleImportProcessor,
        private readonly UserNotificationService $userNotificationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityManager = $this->entityManager;
        $queueRepository = $this->articleImportQueueRepository;
        $queueItem = $queueRepository->claimNextPending();

        if (null === $queueItem) {
            $io->success('No queued article imports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        while (null !== $queueItem) {
            try {
                $importedArticles = $this->articleImportProcessor->process($queueItem);

                $queueItem
                    ->setStatus(ArticleImportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow())
                    ->setErrorMessage(null);

                $entityManager->flush();
                $this->userNotificationService->notifyImportCompleted($queueItem->getRequestedBy()?->getId(), true);
                ++$processedCount;

                $io->success(sprintf(
                    'Imported %d article(s) from queue item %d.',
                    $importedArticles,
                    $queueItem->getId() ?? 0,
                ));
            } catch (\Throwable $exception) {
                $this->logger->error('Article import failed while processing queue item.', [
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
                $this->userNotificationService->notifyImportCompleted($queueItem->getRequestedBy()?->getId(), false);
                ++$failedCount;

                $io->error(sprintf(
                    'Article import failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }

            $queueItem = $queueRepository->claimNextPending();
        }

        if (0 === $failedCount) {
            $io->success(sprintf('Imported %d queued article file(s).', $processedCount));

            return Command::SUCCESS;
        }

        $io->warning(sprintf(
            'Processed %d queued article file(s), but %d import(s) failed.',
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
        \App\Entity\ArticleImportQueue $queueItem,
        string $errorMessage,
        EntityManagerInterface $entityManager,
        ArticleImportQueueRepository $queueRepository,
    ): array
    {
        if ($entityManager->isOpen()) {
            $queueItem
                ->setStatus(ArticleImportQueueStatus::FAILED)
                ->setErrorMessage($errorMessage);

            $entityManager->flush();

            return [$entityManager, $queueRepository];
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass(\App\Entity\ArticleImportQueue::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager for article import queue is not available.');
        }

        $managedQueueItem = $entityManager->find(\App\Entity\ArticleImportQueue::class, $queueItem->getId());
        if (!$managedQueueItem instanceof \App\Entity\ArticleImportQueue) {
            throw new \RuntimeException(sprintf(
                'Unable to reload article import queue item %d after import failure.',
                $queueItem->getId() ?? 0,
            ));
        }

        $managedQueueItem
            ->setStatus(ArticleImportQueueStatus::FAILED)
            ->setErrorMessage($errorMessage);

        $entityManager->flush();

        return [$entityManager, $this->refreshQueueRepository()];
    }

    private function refreshQueueRepository(): ArticleImportQueueRepository
    {
        $repository = $this->managerRegistry->getRepository(\App\Entity\ArticleImportQueue::class);
        if (!$repository instanceof ArticleImportQueueRepository) {
            throw new \RuntimeException('Article import queue repository is not available.');
        }

        return $repository;
    }
}
