<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportProcessor;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly ArticleImportQueueRepository $articleImportQueueRepository,
        private readonly ArticleImportProcessor $articleImportProcessor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueItems = $this->articleImportQueueRepository->findPendingOrderedByCreatedAt();

        if ([] === $queueItems) {
            $io->success('No queued article imports to process.');

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $failedCount = 0;

        foreach ($queueItems as $queueItem) {
            $queueItem
                ->setStatus(ArticleImportQueueStatus::PROCESSING)
                ->setErrorMessage(null);
            $this->entityManager->flush();

            try {
                $importedArticles = $this->articleImportProcessor->process($queueItem);

                $queueItem
                    ->setStatus(ArticleImportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow())
                    ->setErrorMessage(null);

                $this->entityManager->flush();
                ++$processedCount;

                $io->success(sprintf(
                    'Imported %d article(s) from queue item %d.',
                    $importedArticles,
                    $queueItem->getId() ?? 0,
                ));
            } catch (\Throwable $exception) {
                $queueItem
                    ->setStatus(ArticleImportQueueStatus::FAILED)
                    ->setErrorMessage($exception->getMessage());
                $this->entityManager->flush();
                ++$failedCount;

                $io->error(sprintf(
                    'Article import failed for queue item %d: %s',
                    $queueItem->getId() ?? 0,
                    $exception->getMessage()
                ));
            }
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
}
