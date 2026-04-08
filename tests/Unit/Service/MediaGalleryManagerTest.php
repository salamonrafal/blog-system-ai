<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\MediaImage;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedFilePathResolver;
use App\Service\MediaGalleryManager;
use PHPUnit\Framework\TestCase;

final class MediaGalleryManagerTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-media-gallery-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/public/uploads/media', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testDeleteRemovesFileReferencedByEntity(): void
    {
        $path = $this->projectDir.'/public/uploads/media/2026/04/05/delete-me.webp';
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'delete');
        $mediaImage = (new MediaImage())->setFilePath('public/uploads/media/2026/04/05/delete-me.webp');

        $manager = $this->createManager();
        $manager->delete($mediaImage);

        $this->assertFileDoesNotExist($path);
    }

    public function testClearCollectsFailuresAndContinuesDeletingRemainingFiles(): void
    {
        $firstPath = 'public/uploads/media/2026/04/05/delete-me.webp';
        $secondPath = 'public/uploads/media/2026/04/05/delete-me-too.webp';
        $deleteInvocation = 0;

        $managedFileDeleter = $this->createMock(ManagedFileDeleter::class);
        $managedFileDeleter
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function () use (&$deleteInvocation): void {
                if (0 === $deleteInvocation) {
                    ++$deleteInvocation;
                    throw new \RuntimeException('unlink failed');
                }

                ++$deleteInvocation;
            });

        $manager = new MediaGalleryManager(
            $managedFileDeleter,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports', 'public/uploads/media')
        );

        $failedFilePaths = $manager->clear([
            (new MediaImage())->setFilePath($firstPath),
            (new MediaImage())->setFilePath($secondPath),
        ]);

        $this->assertSame([$firstPath], $failedFilePaths);
    }

    private function createManager(): MediaGalleryManager
    {
        return new MediaGalleryManager(
            new ManagedFileDeleter(),
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports', 'public/uploads/media')
        );
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
