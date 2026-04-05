<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MediaOrphanArchiveService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:media:archive-orphans',
    description: 'Archive media files that no longer have a database record.',
)]
class ArchiveOrphanedMediaCommand extends Command
{
    public function __construct(private readonly MediaOrphanArchiveService $mediaOrphanArchiveService)
    {
        parent::__construct();
        $this->setDescription(sprintf(
            'Archive files from %s that no longer have a database record.',
            $this->mediaOrphanArchiveService->getMediaDirectory(),
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->mediaOrphanArchiveService->archiveOrphans();
        $mediaDirectory = $this->mediaOrphanArchiveService->getMediaDirectory();

        if ([] === $result['moved_files']) {
            $io->success(sprintf('No orphaned media files were found in %s.', $mediaDirectory));

            return Command::SUCCESS;
        }

        $io->section('Moved files');
        $io->listing($result['moved_files']);

        if (is_string($result['archive_path']) && '' !== $result['archive_path']) {
            $io->section('Archive');
            $io->listing([$result['archive_path']]);
        }

        $io->success(sprintf(
            'Archived %d orphaned media file(s).',
            count($result['moved_files']),
        ));

        return Command::SUCCESS;
    }
}
