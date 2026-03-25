<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ManagedFileDeleter;
use PHPUnit\Framework\TestCase;

final class ManagedFileDeleterTest extends TestCase
{
    public function testDeleteRemovesExistingManagedFile(): void
    {
        $path = sys_get_temp_dir().'/managed-file-deleter-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($path, '{}');

        try {
            $deleter = new ManagedFileDeleter();

            $deleter->delete($path, 'import');

            $this->assertFileDoesNotExist($path);
        } finally {
            @unlink($path);
        }
    }

    public function testDeleteIgnoresMissingFile(): void
    {
        $path = sys_get_temp_dir().'/managed-file-deleter-missing-'.bin2hex(random_bytes(4)).'.json';
        $deleter = new ManagedFileDeleter();

        $deleter->delete($path, 'import');

        $this->assertFileDoesNotExist($path);
    }
}
