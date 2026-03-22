<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleExportQueue;
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
                ->setExcerpt('Skrot')
                ->setHeadlineImage('/assets/img/example.png')
                ->setHeadlineImageEnabled(false)
                ->setContent('Pelna tresc')
                ->setStatus(ArticleStatus::PUBLISHED)
                ->setPublishedAt(new \DateTimeImmutable('2026-03-22 12:00:00', new \DateTimeZone('Europe/Warsaw')));

            $queueItem = new ArticleExportQueue($article);
            $writer = new ArticleExportFileWriter($projectDir, 'var/exports');

            $relativePath = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$relativePath;

            $this->assertFileExists($absolutePath);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('article-export', $payload['format']);
            $this->assertSame(1, $payload['version']);
            $this->assertSame(1, $payload['article_count']);
            $this->assertCount(1, $payload['article']);
            $this->assertSame('Eksportowany artykul', $payload['article'][0]['title']);
            $this->assertSame('en', $payload['article'][0]['language']);
            $this->assertSame('published', $payload['article'][0]['status']);
            $this->assertSame('Pelna tresc', $payload['article'][0]['content']);
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
}
