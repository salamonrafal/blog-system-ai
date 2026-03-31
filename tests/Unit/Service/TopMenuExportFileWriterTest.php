<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Entity\TopMenuExportQueue;
use App\Enum\TopMenuItemStatus;
use App\Enum\TopMenuItemTargetType;
use App\Repository\TopMenuItemRepository;
use App\Service\TopMenuExportFileWriter;
use PHPUnit\Framework\TestCase;

final class TopMenuExportFileWriterTest extends TestCase
{
    public function testWriteCreatesJsonFileWithWholeMenuHierarchyPayload(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-export-writer-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);

        try {
            $parent = (new TopMenuItem())
                ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
                ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
                ->setPosition(1)
                ->setStatus(TopMenuItemStatus::ACTIVE);
            $this->setEntityId($parent, 10);

            $child = (new TopMenuItem())
                ->setLabels(['pl' => 'AI', 'en' => 'AI'])
                ->setTargetType(TopMenuItemTargetType::ARTICLE_CATEGORY)
                ->setArticleCategory((new ArticleCategory())->setName('AI'))
                ->setParent($parent)
                ->setPosition(2)
                ->setStatus(TopMenuItemStatus::ACTIVE);
            $this->setEntityId($child, 11);
            $this->setEntityId($child->getArticleCategory(), 21);

            $articleItem = (new TopMenuItem())
                ->setLabels(['pl' => 'Wpis', 'en' => 'Entry'])
                ->setTargetType(TopMenuItemTargetType::ARTICLE)
                ->setArticle((new Article())->setTitle('Hello')->setSlug('hello'))
                ->setPosition(3)
                ->setStatus(TopMenuItemStatus::INACTIVE);
            $this->setEntityId($articleItem, 12);
            $this->setEntityId($articleItem->getArticle(), 31);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findForAdminIndex')
                ->willReturn([$parent, $child, $articleItem]);

            $queueItem = new TopMenuExportQueue();
            $this->setEntityId($queueItem, 18);

            $writer = new TopMenuExportFileWriter($repository, $projectDir, 'var/exports');
            $relativePath = $writer->write($queueItem);
            $absolutePath = $projectDir.'/'.$relativePath;

            $this->assertFileExists($absolutePath);
            $this->assertStringStartsWith('var/exports/top-menu-export-', $relativePath);

            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('top-menu-export', $payload['format']);
            $this->assertSame(3, $payload['menu_item_count']);
            $this->assertSame(18, $payload['menu_items'][0]['queue_item_id']);
            $this->assertSame(10, $payload['menu_items'][0]['id']);
            $this->assertNull($payload['menu_items'][0]['parent_id']);
            $this->assertSame(10, $payload['menu_items'][1]['parent_id']);
            $this->assertSame(21, $payload['menu_items'][1]['article_category_id']);
            $this->assertSame(31, $payload['menu_items'][2]['article_id']);
            $this->assertSame('inactive', $payload['menu_items'][2]['status']);
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
