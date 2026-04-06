<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ManagedUploadedFileStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ManagedUploadedFileStorageTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-managed-upload-storage-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testStoreNormalizesClientSidePathSegmentsInOriginalFilename(): void
    {
        $sourcePath = $this->projectDir.'/upload.jpg';
        file_put_contents($sourcePath, 'image-bytes');

        $uploadedFile = new UploadedFile($sourcePath, 'C:\\fakepath\\gallery/hero.jpg', 'image/jpeg', null, true);
        $storage = new ManagedUploadedFileStorage($this->projectDir);

        $storedFile = $storage->store($uploadedFile, 'var/uploads', 'managed-upload', 'image', 'jpg');

        $this->assertSame('hero.jpg', $storedFile['original_filename']);
        $this->assertStringStartsWith('var/uploads/managed-upload-', $storedFile['relative_path']);
        $this->assertStringEndsWith('.jpg', $storedFile['relative_path']);
        $this->assertFileExists($this->projectDir.'/'.$storedFile['relative_path']);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = $path.'/'.$item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);

                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
