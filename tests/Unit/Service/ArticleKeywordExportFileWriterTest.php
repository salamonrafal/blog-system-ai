<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleKeyword;
use App\Entity\ArticleKeywordExportQueue;
use App\Enum\ArticleKeywordLanguage;
use App\Repository\ArticleKeywordRepository;
use App\Service\ArticleKeywordExportFileWriter;
use PHPUnit\Framework\TestCase;

final class ArticleKeywordExportFileWriterTest extends TestCase
{
    public function testWriteCreatesJsonFileWithRestorableKeywordPayload(): void
    {
        $projectDir = sys_get_temp_dir().'/article-keyword-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        try {
            $article = (new Article())
                ->setTitle('PHP 8.4')
                ->setSlug('php-8-4');
            $this->setEntityId($article, 44);

            $keyword = (new ArticleKeyword())
                ->setName('php-8-4')
                ->setLanguage(ArticleKeywordLanguage::EN)
                ->setColor('#ff6600')
                ->addArticle($article);
            $this->setEntityId($keyword, 12);

            $queueItem = new ArticleKeywordExportQueue();
            $this->setEntityId($queueItem, 18);

            $repository = $this->createMock(ArticleKeywordRepository::class);
            $repository
                ->expects($this->once())
                ->method('findForExport')
                ->willReturn([$keyword]);

            $writer = new ArticleKeywordExportFileWriter($repository, $projectDir, 'var/exports');
            $writtenExport = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$writtenExport['file_path'];

            $this->assertFileExists($absolutePath);
            $this->assertSame(1, $writtenExport['items_count']);
            $this->assertStringStartsWith('var/exports/article-keywords-export-', $writtenExport['file_path']);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('article-keyword-export', $payload['format']);
            $this->assertSame(1, $payload['keyword_count']);
            $this->assertSame(18, $payload['keywords'][0]['queue_item_id']);
            $this->assertSame('php-8-4', $payload['keywords'][0]['name']);
            $this->assertSame('en', $payload['keywords'][0]['language']);
            $this->assertSame('#ff6600', $payload['keywords'][0]['color']);
            $this->assertSame([44], $payload['keywords'][0]['article_ids']);
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
