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

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
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

    public function testProcessUpdatesExistingKeywordMatchedByLanguageAndNameAndIgnoresArticles(): void
    {
        $existingKeyword = (new ArticleKeyword())
            ->setName('AI')
            ->setLanguage(ArticleKeywordLanguage::ALL)
            ->setStatus(ArticleCategoryStatus::INACTIVE)
            ->setColor('#abcdef');
        $existingArticle = (new Article())
            ->setTitle('Existing article')
            ->setSlug('existing-article');
        $existingKeyword->addArticle($existingArticle);
        $this->setEntityId($existingKeyword, 10);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock([
            'all|AI' => $existingKeyword,
        ]));
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

    public function testProcessAllowsDuplicateNamesAcrossDifferentLanguages(): void
    {
        $persistedKeywords = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (ArticleKeyword $keyword) use (&$persistedKeywords): void {
                $persistedKeywords[] = $keyword;
            });

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
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

        $importedCount = $processor->process($queueItem);

        $this->assertSame(2, $importedCount);
        $this->assertCount(2, $persistedKeywords);
        $this->assertSame(
            [ArticleKeywordLanguage::PL, ArticleKeywordLanguage::EN],
            array_map(static fn (ArticleKeyword $keyword): ArticleKeywordLanguage => $keyword->getLanguage(), $persistedKeywords),
        );
    }

    public function testProcessThrowsReadableErrorWhenPayloadContainsDuplicateLanguageAndNamePairs(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
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
                    'language' => 'pl',
                    'status' => 'inactive',
                    'color' => null,
                ],
            ],
        ]);

        $this->expectException(ArticleKeywordImportException::class);
        $this->expectExceptionMessage('Fields keywords[1].language and keywords[1].name duplicate values from keywords[0].language and keywords[0].name.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenLanguageIsNotAllowed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
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

    public function testProcessThrowsReadableErrorWhenOptionalColorFieldHasInvalidType(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-keyword-export',
            'version' => 1,
            'keywords' => [[
                'name' => 'AI',
                'language' => 'pl',
                'status' => 'active',
                'color' => ['#123456'],
            ]],
        ]);

        $this->expectException(ArticleKeywordImportException::class);
        $this->expectExceptionMessage('Field keywords[0].color must be a string or null.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenRootJsonValueIsScalar(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock());
        $queueItem = $this->createQueueItemWithRawJson('true');

        $this->expectException(ArticleKeywordImportException::class);
        $this->expectExceptionMessage('Import file root value must be a JSON object.');

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

    private function createQueueItemWithRawJson(string $json): ArticleKeywordImportQueue
    {
        $filename = 'keywords-raw-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($this->projectDir.'/var/imports/'.$filename, $json);

        return (new ArticleKeywordImportQueue())
            ->setOriginalFilename($filename)
            ->setFilePath('var/imports/'.$filename);
    }

    /**
     * @param array<string, ArticleKeyword> $keywordsByKey
     */
    private function createRepositoryMock(array $keywordsByKey = []): ArticleKeywordRepository
    {
        /** @var ArticleKeywordRepository&MockObject $repository */
        $repository = $this->createMock(ArticleKeywordRepository::class);
        $repository
            ->method('findByLanguageAndNamePairs')
            ->willReturnCallback(static function (array $pairs) use ($keywordsByKey): array {
                $foundKeywords = [];

                foreach ($pairs as $pair) {
                    $key = $pair['language']->value.'|'.trim($pair['name']);
                    if (array_key_exists($key, $keywordsByKey)) {
                        $foundKeywords[] = $keywordsByKey[$key];
                    }
                }

                return $foundKeywords;
            });

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
