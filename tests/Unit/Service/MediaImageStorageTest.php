<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MediaImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaImageStorageTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-media-storage-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testStoreMovesImageIntoConfiguredDirectory(): void
    {
        $sourcePath = $this->projectDir.'/upload.webp';
        file_put_contents($sourcePath, base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAUAmJaACdLoB+AADsAD+8ut//NgVzXPv9//S4P0uD9Lg/9KQAAA='));

        $uploadedFile = new UploadedFile($sourcePath, 'hero.webp', 'image/webp', null, true);
        $storage = new MediaImageStorage('public/uploads/media', $this->projectDir);

        $storedFile = $storage->store($uploadedFile);

        $this->assertSame('hero.webp', $storedFile['original_filename']);
        $this->assertStringStartsWith('public/uploads/media/', $storedFile['relative_path']);
        $this->assertStringContainsString('/'.(new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y/m/d').'/', $storedFile['relative_path']);
        $this->assertStringEndsWith('.webp', $storedFile['relative_path']);
        $this->assertSame(68, $storedFile['file_size']);
        $this->assertSame('image/webp', $storedFile['mime_type']);
        $this->assertFileExists($this->projectDir.'/'.$storedFile['relative_path']);
    }

    public function testStoreUsesDetectedMimeTypeToChooseStoredExtension(): void
    {
        $sourcePath = $this->projectDir.'/upload-mismatch.webp';
        file_put_contents($sourcePath, base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAUAmJaACdLoB+AADsAD+8ut//NgVzXPv9//S4P0uD9Lg/9KQAAA='));

        $uploadedFile = new UploadedFile($sourcePath, 'hero.jpg', 'image/jpeg', null, true);
        $storage = new MediaImageStorage('public/uploads/media', $this->projectDir);

        $storedFile = $storage->store($uploadedFile);

        $this->assertStringEndsWith('.webp', $storedFile['relative_path']);
        $this->assertSame('image/webp', $storedFile['mime_type']);
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
