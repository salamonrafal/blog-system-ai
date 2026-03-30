<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ArticleCategory;
use App\Entity\CategoryExportQueue;
use App\Service\CategoryExportFileWriter;
use PHPUnit\Framework\TestCase;

final class CategoryExportFileWriterTest extends TestCase
{
    public function testWriteCreatesJsonFileWithRestorableCategoryPayload(): void
    {
        $projectDir = sys_get_temp_dir().'/category-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        try {
            $category = (new ArticleCategory())
                ->setName('AI & Data')
                ->setShortDescription('Kategoria techniczna')
                ->setTitles(['pl' => 'AI i dane', 'en' => 'AI and data'])
                ->setDescriptions(['pl' => 'Opis PL', 'en' => 'Description EN'])
                ->setIcon('ph ph-cpu');

            $queueItem = new CategoryExportQueue($category);
            $this->setEntityId($queueItem, 18);
            $writer = new CategoryExportFileWriter($projectDir, 'var/exports');

            $relativePath = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$relativePath;

            $this->assertFileExists($absolutePath);
            $this->assertStringStartsWith('var/exports/category-AI-Data-export-', $relativePath);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('category-export', $payload['format']);
            $this->assertSame(1, $payload['category_count']);
            $this->assertSame(18, $payload['category'][0]['queue_item_id']);
            $this->assertSame('AI & Data', $payload['category'][0]['name']);
            $this->assertSame('AI and data', $payload['category'][0]['titles']['en']);
            $this->assertSame(
                basename($relativePath, '.json'),
                sprintf(
                    'category-AI-Data-export-%s-%s',
                    (new \DateTimeImmutable($payload['exported_at']))->setTimezone(new \DateTimeZone('UTC'))->format('Ymd-His'),
                    substr((string) preg_replace('/^category-AI-Data-export-\d{8}-\d{6}-/', '', basename($relativePath, '.json')), 0)
                )
            );
        } finally {
            $this->removeDirectory($projectDir);
        }
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

            unlink($path);
        }

        rmdir($directory);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}
