<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ArchiveOrphanedMediaCommand;
use App\Service\MediaOrphanArchiveService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ArchiveOrphanedMediaCommandTest extends TestCase
{
    public function testExecutePrintsMovedFilesAndArchivePath(): void
    {
        /** @var MediaOrphanArchiveService&MockObject $service */
        $service = $this->createMock(MediaOrphanArchiveService::class);
        $service
            ->expects($this->once())
            ->method('archiveOrphans')
            ->willReturn([
                'archive_path' => 'var/media-orphans/media-orphans-20260405-120000.zip',
                'moved_files' => [
                    'public/uploads/media/2026/04/05/orphan-one.webp',
                    'public/uploads/media/2026/04/05/orphan-two.webp',
                ],
            ]);

        $tester = new CommandTester(new ArchiveOrphanedMediaCommand($service));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Moved files', $tester->getDisplay());
        $this->assertStringContainsString('public/uploads/media/2026/04/05/orphan-one.webp', $tester->getDisplay());
        $this->assertStringContainsString('var/media-orphans/media-orphans-20260405-120000.zip', $tester->getDisplay());
        $this->assertStringContainsString('Archived 2 orphaned media file(s).', $tester->getDisplay());
    }

    public function testExecuteReportsWhenNoOrphanedFilesWereFound(): void
    {
        /** @var MediaOrphanArchiveService&MockObject $service */
        $service = $this->createMock(MediaOrphanArchiveService::class);
        $service
            ->expects($this->once())
            ->method('archiveOrphans')
            ->willReturn([
                'archive_path' => null,
                'moved_files' => [],
            ]);

        $tester = new CommandTester(new ArchiveOrphanedMediaCommand($service));
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No orphaned media files were found in public/uploads/media.', $tester->getDisplay());
    }
}
