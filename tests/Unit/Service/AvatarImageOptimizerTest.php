<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AvatarImageOptimizer;
use PHPUnit\Framework\TestCase;

final class AvatarImageOptimizerTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-avatar-optimizer-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testReplaceOptimizedFileRestoresOriginalWhenFinalWriteFails(): void
    {
        $absolutePath = $this->projectDir.'/avatar.jpg';
        $temporaryPath = $absolutePath.'.tmp';
        file_put_contents($absolutePath, 'original-avatar');
        file_put_contents($temporaryPath, 'optimized-avatar');

        $optimizer = new FailingReplacementAvatarImageOptimizer();
        $optimizer->invokeReplaceOptimizedFile($temporaryPath, $absolutePath);

        $this->assertFileExists($absolutePath);
        $this->assertSame('original-avatar', file_get_contents($absolutePath));
        $this->assertFileDoesNotExist($temporaryPath);
        $this->assertCount(0, glob($absolutePath.'.backup-*') ?: []);
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

final class FailingReplacementAvatarImageOptimizer extends AvatarImageOptimizer
{
    public function invokeReplaceOptimizedFile(string $temporaryPath, string $absolutePath): void
    {
        $invoker = \Closure::bind(
            static function (AvatarImageOptimizer $optimizer, string $tmp, string $path): void {
                $optimizer->replaceOptimizedFile($tmp, $path);
            },
            null,
            AvatarImageOptimizer::class,
        );

        $invoker($this, $temporaryPath, $absolutePath);
    }

    protected function moveFile(string $from, string $to): bool
    {
        if (str_ends_with($from, '.tmp')) {
            return false;
        }

        return parent::moveFile($from, $to);
    }

    protected function copyFile(string $from, string $to): bool
    {
        if (str_ends_with($from, '.tmp')) {
            return false;
        }

        return parent::copyFile($from, $to);
    }
}
