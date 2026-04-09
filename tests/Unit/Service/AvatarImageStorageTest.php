<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AvatarImageOptimizer;
use App\Service\AvatarImageStorage;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedUploadedFileStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AvatarImageStorageTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-avatar-storage-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/public/uploads/avatars', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testStoreReturnsManagedPublicPath(): void
    {
        $sourcePath = $this->projectDir.'/avatar.jpg';
        file_put_contents($sourcePath, $this->createTinyJpeg());

        $uploadedFile = new UploadedFile($sourcePath, 'profile.jpg', 'image/jpeg', null, true);
        $storage = new AvatarImageStorage(
            'public/uploads/avatars',
            $this->projectDir,
            new ManagedUploadedFileStorage($this->projectDir),
            new AvatarImageOptimizer(),
            new ManagedFileDeleter(),
        );

        $storedFile = $storage->store($uploadedFile);

        $this->assertStringStartsWith('public/uploads/avatars/', $storedFile['relative_path']);
        $this->assertStringStartsWith('/uploads/avatars/', $storedFile['public_path']);
        $this->assertFileExists($this->projectDir.'/'.$storedFile['relative_path']);
        $this->assertSame('profile.jpg', $storedFile['original_filename']);
    }

    public function testStoreDeletesPreviouslyManagedAvatarWhenReplacing(): void
    {
        $existingDirectory = $this->projectDir.'/public/uploads/avatars/2026/04/08';
        mkdir($existingDirectory, 0777, true);
        $existingPath = $existingDirectory.'/old-avatar.jpg';
        file_put_contents($existingPath, 'old-avatar');

        $sourcePath = $this->projectDir.'/avatar-replacement.jpg';
        file_put_contents($sourcePath, $this->createTinyJpeg());

        $uploadedFile = new UploadedFile($sourcePath, 'replacement.jpg', 'image/jpeg', null, true);
        $storage = new AvatarImageStorage(
            'public/uploads/avatars',
            $this->projectDir,
            new ManagedUploadedFileStorage($this->projectDir),
            new AvatarImageOptimizer(),
            new ManagedFileDeleter(),
        );

        $storage->store($uploadedFile, '/uploads/avatars/2026/04/08/old-avatar.jpg');

        $this->assertFileDoesNotExist($existingPath);
    }

    public function testStoreDeletesPreviouslyManagedAvatarFromConfigurableDirectory(): void
    {
        mkdir($this->projectDir.'/custom/avatars/2026/04/08', 0777, true);
        $existingPath = $this->projectDir.'/custom/avatars/2026/04/08/old-avatar.jpg';
        file_put_contents($existingPath, 'old-avatar');

        $sourcePath = $this->projectDir.'/avatar-custom-replacement.jpg';
        file_put_contents($sourcePath, $this->createTinyJpeg());

        $uploadedFile = new UploadedFile($sourcePath, 'replacement.jpg', 'image/jpeg', null, true);
        $storage = new AvatarImageStorage(
            'custom/avatars',
            $this->projectDir,
            new ManagedUploadedFileStorage($this->projectDir),
            new AvatarImageOptimizer(),
            new ManagedFileDeleter(),
        );

        $storage->store($uploadedFile, '/custom/avatars/2026/04/08/old-avatar.jpg');

        $this->assertFileDoesNotExist($existingPath);
    }

    public function testStoreSucceedsWhenDeletingPreviousAvatarFails(): void
    {
        $existingDirectory = $this->projectDir.'/public/uploads/avatars/2026/04/08';
        mkdir($existingDirectory, 0777, true);
        $existingPath = $existingDirectory.'/old-avatar.jpg';
        file_put_contents($existingPath, 'old-avatar');

        $sourcePath = $this->projectDir.'/avatar-delete-failure.jpg';
        file_put_contents($sourcePath, $this->createTinyJpeg());

        $uploadedFile = new UploadedFile($sourcePath, 'replacement.jpg', 'image/jpeg', null, true);

        /** @var ManagedFileDeleter&MockObject $managedFileDeleter */
        $managedFileDeleter = $this->createMock(ManagedFileDeleter::class);
        $managedFileDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($this->anything(), 'avatar')
            ->willThrowException(new \RuntimeException('Cannot delete avatar'));

        $storage = new AvatarImageStorage(
            'public/uploads/avatars',
            $this->projectDir,
            new ManagedUploadedFileStorage($this->projectDir),
            new AvatarImageOptimizer(),
            $managedFileDeleter,
        );

        $storedFile = $storage->store($uploadedFile, '/uploads/avatars/2026/04/08/old-avatar.jpg');

        $this->assertStringStartsWith('/uploads/avatars/', $storedFile['public_path']);
        $this->assertFileExists($this->projectDir.'/'.$storedFile['relative_path']);
        $this->assertFileExists($existingPath);
    }

    private function createTinyJpeg(): string
    {
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFRUVFRUVFRUVFRUVFRUQFRUWFhUVFRUYHSggGBolHRUVITEhJSkrLi4uFx8zODMsNygtLisBCgoKDg0OGhAQGzIlHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAgACAMBIgACEQEDEQH/xAAXAAEBAQEAAAAAAAAAAAAAAAAAAQID/8QAFhEBAQEAAAAAAAAAAAAAAAAAAQAC/9oADAMBAAIQAxAAAAHJrKkP/8QAHBAAAgICAwAAAAAAAAAAAAAAAAECEQMhMRIy/9oACAEBAAEFAvVnMZl5Y0//xAAVEQEBAAAAAAAAAAAAAAAAAAABAP/aAAgBAwEBPwGn/8QAFhEBAQEAAAAAAAAAAAAAAAAAABEB/9oACAECAQE/Aaf/xAAbEAACAQUAAAAAAAAAAAAAAAAAAREhMUFhcf/aAAgBAQAGPwJx2uM2f//EABsQAQADAQEBAQAAAAAAAAAAAAEAESExQVFh/9oACAEBAAE/IXbNk2u0l8Q8Hq6m1F//2gAMAwEAAgADAAAAENAP/8QAFhEBAQEAAAAAAAAAAAAAAAAAARAR/9oACAEDAQE/EEqf/8QAFhEBAQEAAAAAAAAAAAAAAAAAARAR/9oACAECAQE/EEtP/8QAGxABAQADAQEBAAAAAAAAAAAAAREAITFBUWH/2gAIAQEAAT8Qh1p4kMsS2y8FcZbK9mHzonlWQhV8/9k=',
            true,
        );

        if (!is_string($jpeg) || '' === $jpeg) {
            $this->fail('Failed to decode embedded JPEG test image.');
        }

        return $jpeg;
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
