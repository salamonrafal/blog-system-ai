<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\MediaImageRepository;
use App\Service\MediaOrphanArchiveService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MediaOrphanArchiveServiceTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-media-orphans-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/public/uploads/media', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testArchiveOrphansMovesUntrackedFilesAndCreatesArchive(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            self::markTestSkipped('ZipArchive extension is not available.');
        }

        $trackedPath = 'public/uploads/media/2026/04/05/tracked.webp';
        $orphanPathOne = 'public/uploads/media/2026/04/05/orphan-one.webp';
        $orphanPathTwo = 'public/uploads/media/loose/orphan-two.png';

        $this->createFile($trackedPath, 'tracked');
        $this->createFile($orphanPathOne, 'orphan-one');
        $this->createFile($orphanPathTwo, 'orphan-two');

        $service = $this->createService([$trackedPath]);
        $result = $service->archiveOrphans();

        $this->assertSame([$orphanPathOne, $orphanPathTwo], $result['moved_files']);
        $this->assertIsString($result['archive_path']);
        $this->assertFileExists($this->projectDir.'/'.$result['archive_path']);
        $this->assertFileExists($this->projectDir.'/'.$trackedPath);
        $this->assertFileDoesNotExist($this->projectDir.'/'.$orphanPathOne);
        $this->assertFileDoesNotExist($this->projectDir.'/'.$orphanPathTwo);
        $this->assertArchiveContains($this->projectDir.'/'.$result['archive_path'], [
            $orphanPathOne,
            $orphanPathTwo,
        ]);
        $this->assertDirectoryDoesNotExist($this->projectDir.'/var/media-orphans/tmp');
    }

    public function testArchiveOrphansReturnsEmptyResultWhenAllFilesAreTracked(): void
    {
        $trackedPath = 'public/uploads/media/2026/04/05/tracked.webp';
        $this->createFile($trackedPath, 'tracked');

        $service = $this->createService([$trackedPath]);
        $result = $service->archiveOrphans();

        $this->assertNull($result['archive_path']);
        $this->assertSame([], $result['moved_files']);
        $this->assertFileExists($this->projectDir.'/'.$trackedPath);
    }

    public function testArchiveOrphansIgnoresConfiguredFilenamesSuchAsGitkeep(): void
    {
        $gitkeepPath = 'public/uploads/media/.gitkeep';
        $this->createFile($gitkeepPath, '');

        $service = $this->createService([], ['.gitkeep']);
        $result = $service->archiveOrphans();

        $this->assertNull($result['archive_path']);
        $this->assertSame([], $result['moved_files']);
        $this->assertFileExists($this->projectDir.'/'.$gitkeepPath);
    }

    public function testArchiveOrphansRestoresMovedFilesWhenArchiveCreationFails(): void
    {
        $orphanPath = 'public/uploads/media/2026/04/05/orphan-one.webp';
        $this->createFile($orphanPath, 'orphan-one');

        /** @var MediaImageRepository&MockObject $repository */
        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->method('findAllStoredFilePaths')
            ->willReturn([]);

        $service = new class($repository, $this->projectDir, 'public/uploads/media') extends MediaOrphanArchiveService {
            protected function createArchiveFromStagingDirectory(string $stagingDirectory): string
            {
                throw new \RuntimeException('Archive creation failed.');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Archive creation failed.');

        try {
            $service->archiveOrphans();
        } finally {
            $this->assertFileExists($this->projectDir.'/'.$orphanPath);
            $this->assertDirectoryDoesNotExist($this->projectDir.'/var/media-orphans/tmp');
        }
    }

    public function testArchiveOrphansRethrowsOriginalExceptionWhenRollbackFails(): void
    {
        $orphanPath = 'public/uploads/media/2026/04/05/orphan-one.webp';
        $this->createFile($orphanPath, 'orphan-one');

        /** @var MediaImageRepository&MockObject $repository */
        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->method('findAllStoredFilePaths')
            ->willReturn([]);

        $service = new class($repository, $this->projectDir, 'public/uploads/media') extends MediaOrphanArchiveService {
            protected function createArchiveFromStagingDirectory(string $stagingDirectory): string
            {
                throw new \RuntimeException('Archive creation failed.');
            }

            protected function rollbackMovedFiles(array $movedFiles): void
            {
                throw new \RuntimeException('Rollback failed.');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Archive creation failed.');

        try {
            $service->archiveOrphans();
        } finally {
            $this->assertDirectoryDoesNotExist($this->projectDir.'/var/media-orphans/tmp');
        }
    }

    public function testArchiveOrphansDoesNotRollbackFilesWhenPostArchiveCleanupFails(): void
    {
        $orphanPath = 'public/uploads/media/2026/04/05/orphan-one.webp';
        $this->createFile($orphanPath, 'orphan-one');

        /** @var MediaImageRepository&MockObject $repository */
        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->method('findAllStoredFilePaths')
            ->willReturn([]);

        $archivePath = $this->projectDir.'/var/media-orphans/media-orphans-test.zip';
        $service = new class($repository, $this->projectDir, 'public/uploads/media', ['.gitkeep'], $archivePath) extends MediaOrphanArchiveService {
            public function __construct(
                MediaImageRepository $mediaImageRepository,
                string $projectDir,
                string $mediaDirectory,
                array $ignoredFilenames,
                private readonly string $archivePath,
            ) {
                parent::__construct($mediaImageRepository, $projectDir, $mediaDirectory, $ignoredFilenames);
            }

            protected function createArchiveFromStagingDirectory(string $stagingDirectory): string
            {
                if (!is_dir(dirname($this->archivePath))) {
                    mkdir(dirname($this->archivePath), 0775, true);
                }

                file_put_contents($this->archivePath, 'zip-bytes');

                return $this->archivePath;
            }

            protected function finalizeArchivedOrphans(string $stagingDirectory): void
            {
                throw new \RuntimeException('cleanup failed');
            }
        };

        $result = $service->archiveOrphans();

        $this->assertSame([$orphanPath], $result['moved_files']);
        $this->assertSame('var/media-orphans/media-orphans-test.zip', $result['archive_path']);
        $this->assertFileExists($archivePath);
        $this->assertFileDoesNotExist($this->projectDir.'/'.$orphanPath);
    }

    private function createService(array $storedFilePaths, array $ignoredFilenames = ['.gitkeep']): MediaOrphanArchiveService
    {
        /** @var MediaImageRepository&MockObject $repository */
        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->method('findAllStoredFilePaths')
            ->willReturn($storedFilePaths);

        return new MediaOrphanArchiveService($repository, $this->projectDir, 'public/uploads/media', $ignoredFilenames);
    }

    private function createFile(string $relativePath, string $contents): void
    {
        $absolutePath = $this->projectDir.'/'.$relativePath;
        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }
        file_put_contents($absolutePath, $contents);
    }

    /**
     * @param list<string> $expectedEntries
     */
    private function assertArchiveContains(string $archivePath, array $expectedEntries): void
    {
        $archive = new \ZipArchive();
        $result = $archive->open($archivePath);

        $this->assertTrue(true === $result, 'Archive should open successfully.');

        $entries = [];
        for ($index = 0; $index < $archive->numFiles; ++$index) {
            $name = $archive->getNameIndex($index);
            if (false !== $name) {
                $entries[] = $name;
            }
        }
        $archive->close();

        sort($entries);
        sort($expectedEntries);

        $this->assertSame($expectedEntries, $entries);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
