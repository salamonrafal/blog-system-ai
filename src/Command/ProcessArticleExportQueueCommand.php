<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ArticleExport;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Repository\ArticleExportQueueRepository;
use App\Service\ArticleExportFileWriter;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly ArticleExportQueueRepository $articleExportQueueRepository,
        private readonly ArticleExportFileWriter $articleExportFileWriter,
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
            try {
                $filePath = $this->articleExportFileWriter->write($queueItem);

                $articleExport = (new ArticleExport())
                    ->setStatus(ArticleExportStatus::NEW)
                    ->setType(ArticleExportType::ARTICLES)
                    ->setFilePath($filePath)
                    ->setArticleCount(1);

                $queueItem
                    ->setStatus(ArticleExportQueueStatus::COMPLETED)
                    ->setProcessedAt($this->utcNow());

                $this->entityManager->persist($articleExport);
                $this->entityManager->flush();
                ++$processedCount;
            } catch (\Throwable $exception) {
                $queueItem->setStatus(ArticleExportQueueStatus::FAILED);
                $this->entityManager->flush();
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
}
