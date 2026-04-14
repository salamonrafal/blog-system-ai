<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\ArticleExportQueue;
use App\Entity\User;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Service\ArticleExportFileWriter;
use PHPUnit\Framework\TestCase;

final class ArticleExportFileWriterTest extends TestCase
{
    public function testWriteCreatesJsonFileWithRestorableArticlePayload(): void
    {
        $projectDir = sys_get_temp_dir().'/article-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        try {
            $article = (new Article())
                ->setTitle('Eksportowany artykul')
                ->setLanguage(ArticleLanguage::EN)
                ->setSlug('eksportowany-artykul')
                ->setCategory((new ArticleCategory())->setName('AI')->setSlug('ai'))
                ->setExcerpt('Skrot')
                ->setHeadlineImage('/assets/img/example.png')
                ->setHeadlineImageEnabled(false)
                ->setTableOfContentsEnabled(true)
                ->setContent('Pelna tresc')
                ->setStatus(ArticleStatus::PUBLISHED)
                ->setPublishedAt(new \DateTimeImmutable('2026-03-22 12:00:00', new \DateTimeZone('Europe/Warsaw')));

            $queueItem = new ArticleExportQueue($article);
            $this->setEntityId($queueItem, 12);
            $user = (new User())
                ->setEmail('exporter@example.com')
                ->setFullName('Eksporter');
            $this->setEntityId($user, 7);
            $queueItem->setRequestedBy($user);
            $writer = new ArticleExportFileWriter($projectDir, 'var/exports');

            $relativePath = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$relativePath;

            $this->assertFileExists($absolutePath);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('article-export', $payload['format']);
            $this->assertSame(1, $payload['version']);
            $this->assertSame(7, $payload['exported_by']['id']);
            $this->assertSame('Eksporter', $payload['exported_by']['display_name']);
            $this->assertArrayNotHasKey('email', $payload['exported_by']);
            $this->assertSame(1, $payload['article_count']);
            $this->assertCount(1, $payload['article']);
            $this->assertSame(12, $payload['article'][0]['queue_item_id']);
            $this->assertSame('Eksportowany artykul', $payload['article'][0]['title']);
            $this->assertSame('en', $payload['article'][0]['language']);
            $this->assertSame('ai', $payload['article'][0]['category_slug']);
            $this->assertSame('published', $payload['article'][0]['status']);
            $this->assertSame('Pelna tresc', $payload['article'][0]['content']);
            $this->assertTrue($payload['article'][0]['table_of_contents_enabled']);
            $this->assertSame(
                basename($relativePath, '.json'),
                sprintf(
                    'article-eksportowany-artykul-export-%s-%s',
                    (new \DateTimeImmutable($payload['exported_at']))->setTimezone(new \DateTimeZone('UTC'))->format('Ymd-His'),
                    substr((string) preg_replace('/^article-eksportowany-artykul-export-\d{8}-\d{6}-/', '', basename($relativePath, '.json')), 0)
                )
            );
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testWriteSanitizesSlugBeforeUsingItInExportFileName(): void
    {
        $projectDir = sys_get_temp_dir().'/article-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        try {
            $article = (new Article())
                ->setTitle('Eksportowany artykul')
                ->setLanguage(ArticleLanguage::EN)
                ->setSlug('foo/bar baz')
                ->setContent('Pelna tresc')
                ->setStatus(ArticleStatus::DRAFT);

            $queueItem = new ArticleExportQueue($article);
            $writer = new ArticleExportFileWriter($projectDir, 'var/exports');

            $relativePath = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$relativePath;

            $this->assertFileExists($absolutePath);
            $this->assertStringStartsWith('var/exports/article-foo-bar-baz-export-', $relativePath);
            $this->assertStringNotContainsString('/bar', $relativePath);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('foo/bar baz', $payload['article'][0]['slug']);
            $this->assertNull($payload['article'][0]['category_slug']);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testDeleteRemovesExistingExportFile(): void
    {
        $projectDir = sys_get_temp_dir().'/article-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/var/exports', 0775, true);

        try {
            $writer = new ArticleExportFileWriter($projectDir, 'var/exports');
            $relativePath = 'var/exports/article-export.json';
            $absolutePath = $projectDir.'/'.$relativePath;
            file_put_contents($absolutePath, '{}');

            $writer->delete($relativePath);

            $this->assertFileDoesNotExist($absolutePath);
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
