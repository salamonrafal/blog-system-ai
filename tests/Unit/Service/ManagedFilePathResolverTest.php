<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ManagedFilePathResolver;
use PHPUnit\Framework\TestCase;

final class ManagedFilePathResolverTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/managed-file-path-resolver-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/var/exports', 0775, true);
        mkdir($this->projectDir.'/var/imports', 0775, true);
        mkdir($this->projectDir.'/public/uploads/media', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testResolveExportPathReturnsAbsolutePathInsideManagedDirectory(): void
    {
        $resolver = $this->createResolver();
        $path = $this->projectDir.'/var/exports/article.json';
        file_put_contents($path, '{}');

        $this->assertSame($path, $resolver->resolveExportPath('var/exports/article.json'));
    }

    public function testResolveImportPathReturnsNullForFileOutsideManagedDirectory(): void
    {
        $resolver = $this->createResolver();
        $path = $this->projectDir.'/outside.json';
        file_put_contents($path, '{}');

        $this->assertNull($resolver->resolveImportPath('outside.json'));
    }

    public function testResolveMediaPathReturnsAbsolutePathInsideManagedDirectory(): void
    {
        $resolver = $this->createResolver();
        $path = $this->projectDir.'/public/uploads/media/hero.webp';
        file_put_contents($path, 'image');

        $this->assertSame($path, $resolver->resolveMediaPath('public/uploads/media/hero.webp'));
    }

    private function createResolver(): ManagedFilePathResolver
    {
        return new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports', 'public/uploads/media');
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
