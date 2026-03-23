<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ArticleImportStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ArticleImportStorageTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-import-storage-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testStoreMovesFileIntoConfiguredDirectory(): void
    {
        $sourcePath = $this->projectDir.'/upload.json';
        file_put_contents($sourcePath, '{"article":[]}');

        $uploadedFile = new UploadedFile($sourcePath, 'article-export.json', 'application/json', null, true);
        $storage = new ArticleImportStorage($this->projectDir, 'var/imports');

        $storedFile = $storage->store($uploadedFile);

        $this->assertSame('article-export.json', $storedFile['original_filename']);
        $this->assertStringStartsWith('var/imports/article-import-', $storedFile['relative_path']);
        $this->assertStringEndsWith('.json', $storedFile['relative_path']);
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
