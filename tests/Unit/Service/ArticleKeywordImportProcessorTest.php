<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleKeyword;
use App\Entity\ArticleKeywordImportQueue;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use App\Exception\ArticleKeywordImportException;
use App\Repository\ArticleKeywordRepository;
use App\Service\ArticleKeywordImportProcessor;
use App\Service\ManagedFilePathResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ArticleKeywordImportProcessorTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/article-keyword-import-processor-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/var/imports', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testProcessCreatesNewKeywordFromValidExport(): void
    {
        $capturedKeyword = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleKeyword $keyword) use (&$capturedKeyword): void {
                $capturedKeyword = $keyword;
            });

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-keyword-export',
            'version' => 1,
            'keywords' => [[
                'name' => 'AI',
                'language' => 'pl',
                'status' => 'active',
                'color' => '#123456',
                'article_ids' => [1, 2],
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertInstanceOf(ArticleKeyword::class, $capturedKeyword);
        $this->assertSame('AI', $capturedKeyword->getName());
        $this->assertSame(ArticleKeywordLanguage::PL, $capturedKeyword->getLanguage());
        $this->assertSame(ArticleCategoryStatus::ACTIVE, $capturedKeyword->getStatus());
        $this->assertSame('#123456', $capturedKeyword->getColor());
    }

    public function testProcessUpdatesExistingKeywordMatchedByNameAndIgnoresArticles(): void
    {
        $existingKeyword = (new ArticleKeyword())
            ->setName('AI')
            ->setLanguage(ArticleKeywordLanguage::EN)
            ->setStatus(ArticleCategoryStatus::INACTIVE)
            ->setColor('#abcdef');
        $existingArticle = (new Article())
            ->setTitle('Existing article')
            ->setSlug('existing-article');
        $existingKeyword->addArticle($existingArticle);
        $this->setEntityId($existingKeyword, 10);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock($existingKeyword));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-keyword-export',
            'version' => 1,
            'keywords' => [[
                'name' => 'AI',
                'language' => 'all',
                'status' => 'active',
                'color' => '#654321',
                'article_ids' => [999],
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertSame(ArticleKeywordLanguage::ALL, $existingKeyword->getLanguage());
        $this->assertSame(ArticleCategoryStatus::ACTIVE, $existingKeyword->getStatus());
        $this->assertSame('#654321', $existingKeyword->getColor());
        $this->assertCount(1, $existingKeyword->getArticles());
        $this->assertTrue($existingKeyword->getArticles()->contains($existingArticle));
    }

    public function testProcessThrowsReadableErrorWhenPayloadContainsDuplicateNames(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-keyword-export',
            'version' => 1,
            'keywords' => [
                [
                    'name' => 'AI',
                    'language' => 'pl',
                    'status' => 'active',
                    'color' => null,
                ],
                [
                    'name' => 'AI',
                    'language' => 'en',
                    'status' => 'inactive',
                    'color' => null,
                ],
            ],
        ]);

        $this->expectException(ArticleKeywordImportException::class);
        $this->expectExceptionMessage('Field keywords[1].name duplicates value from keywords[0].name.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenLanguageIsNotAllowed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-keyword-export',
            'version' => 1,
            'keywords' => [[
                'name' => 'AI',
                'language' => 'de',
                'status' => 'active',
                'color' => null,
            ]],
        ]);

        $this->expectException(ArticleKeywordImportException::class);
        $this->expectExceptionMessage('Allowed values: all, pl, en.');

        $processor->process($queueItem);
    }

    private function createProcessor(
        EntityManagerInterface $entityManager,
        ArticleKeywordRepository $repository,
    ): ArticleKeywordImportProcessor {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        return new ArticleKeywordImportProcessor(
            $repository,
            $validator,
            $entityManager,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports'),
        );
    }

    private function createQueueItemWithPayload(array $payload): ArticleKeywordImportQueue
    {
        $filename = 'keywords-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($this->projectDir.'/var/imports/'.$filename, json_encode($payload, JSON_THROW_ON_ERROR));

        return (new ArticleKeywordImportQueue())
            ->setOriginalFilename($filename)
            ->setFilePath('var/imports/'.$filename);
    }

    private function createRepositoryMock(?ArticleKeyword $keyword): ArticleKeywordRepository
    {
        /** @var ArticleKeywordRepository&MockObject $repository */
        $repository = $this->createMock(ArticleKeywordRepository::class);
        $repository
            ->method('findOneByName')
            ->willReturn($keyword);

        return $repository;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }
}
