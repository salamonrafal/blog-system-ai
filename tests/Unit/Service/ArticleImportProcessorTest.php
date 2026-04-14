<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleImportQueue;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Exception\ArticleImportException;
use App\Repository\ArticleRepository;
use App\Service\ArticleImportProcessor;
use App\Service\ManagedFilePathResolver;
use App\Service\ArticlePublisher;
use App\Service\ArticleSlugger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ArticleImportProcessorTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/article-import-processor-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/var/imports', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testProcessCreatesNewArticleFromValidExport(): void
    {
        $capturedArticle = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (Article $article) use (&$capturedArticle): void {
                $capturedArticle = $article;
            });

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Importowany artykul',
                'language' => 'en',
                'slug' => 'importowany-artykul',
                'excerpt' => 'Krotki opis',
                'headline_image' => '/assets/img/example.png',
                'headline_image_enabled' => true,
                'table_of_contents_enabled' => true,
                'content' => 'Pelna tresc',
                'status' => 'published',
                'published_at' => '2026-03-23T10:00:00+00:00',
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertInstanceOf(Article::class, $capturedArticle);
        $this->assertSame('Importowany artykul', $capturedArticle->getTitle());
        $this->assertSame(ArticleLanguage::EN, $capturedArticle->getLanguage());
        $this->assertSame('importowany-artykul', $capturedArticle->getSlug());
        $this->assertTrue($capturedArticle->isTableOfContentsEnabled());
        $this->assertSame(ArticleStatus::PUBLISHED, $capturedArticle->getStatus());
        $this->assertSame('2026-03-23 10:00:00', $capturedArticle->getPublishedAt()?->format('Y-m-d H:i:s'));
    }

    public function testProcessUpdatesExistingArticleMatchedBySlug(): void
    {
        $originalCreatedAt = new \DateTimeImmutable('2026-03-10 08:00:00', new \DateTimeZone('UTC'));
        $existingArticle = (new Article())
            ->setTitle('Stary tytul')
            ->setSlug('importowany-artykul')
            ->setContent('Stara tresc')
            ->setStatus(ArticleStatus::DRAFT);
        $this->setEntityId($existingArticle, 10);
        $this->setArticleCreatedAt($existingArticle, $originalCreatedAt);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock($existingArticle, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Nowy tytul',
                'language' => 'pl',
                'slug' => 'importowany-artykul',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => false,
                'table_of_contents_enabled' => true,
                'content' => 'Nowa tresc',
                'status' => 'review',
                'published_at' => null,
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertSame('Nowy tytul', $existingArticle->getTitle());
        $this->assertSame(ArticleLanguage::PL, $existingArticle->getLanguage());
        $this->assertSame('Nowa tresc', $existingArticle->getContent());
        $this->assertTrue($existingArticle->isTableOfContentsEnabled());
        $this->assertSame(ArticleStatus::REVIEW, $existingArticle->getStatus());
        $this->assertNull($existingArticle->getPublishedAt());
        $this->assertSame(
            $originalCreatedAt->format(\DateTimeInterface::ATOM),
            $existingArticle->getCreatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    public function testProcessDefaultsTableOfContentsToFalseWhenFieldIsMissing(): void
    {
        $capturedArticle = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (Article $article) use (&$capturedArticle): void {
                $capturedArticle = $article;
            });

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Importowany artykul',
                'language' => 'en',
                'slug' => 'importowany-artykul',
                'excerpt' => 'Krotki opis',
                'headline_image' => '/assets/img/example.png',
                'headline_image_enabled' => true,
                'content' => 'Pelna tresc',
                'status' => 'published',
                'published_at' => '2026-03-23T10:00:00+00:00',
            ]],
        ]);

        $processor->process($queueItem);

        $this->assertInstanceOf(Article::class, $capturedArticle);
        $this->assertFalse($capturedArticle->isTableOfContentsEnabled());
    }

    public function testProcessThrowsReadableErrorWhenPayloadIsInvalid(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => '',
                'language' => 'pl',
                'slug' => 'bledny-artykul',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].title is required.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenLanguageIsNotSupported(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'de',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Allowed values: pl, en');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenSlugIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => '   ',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].slug is required.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenStatusIsNotAllowed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'invalid-status',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Allowed values: draft, review, published, archived');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenContentIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => '   ',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].content is required.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenImportFileDoesNotExist(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithFilePath('var/imports/missing.json');

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Import file does not exist or is outside the allowed directory.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenImportFileIsOutsideAllowedDirectory(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);

        $outsideFile = $this->projectDir.'/outside.json';
        file_put_contents($outsideFile, '{}');

        $queueItem = $this->createQueueItemWithFilePath('outside.json');

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Import file does not exist or is outside the allowed directory.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenImportFileContainsInvalidJson(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithRawContents('{invalid json');

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Import file does not contain valid JSON.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenFormatIsNotSupported(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'unsupported',
            'version' => 1,
            'article' => [['slug' => 'test', 'title' => 'Test', 'language' => 'pl', 'content' => 'Tresc', 'status' => 'draft', 'headline_image_enabled' => true]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Unsupported import file format.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenVersionIsNotSupported(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 2,
            'article' => [['slug' => 'test', 'title' => 'Test', 'language' => 'pl', 'content' => 'Tresc', 'status' => 'draft', 'headline_image_enabled' => true]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Unsupported import file version.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPayloadHasNoArticles(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Import file does not contain any articles.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPayloadContainsMoreThanOneArticle(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [
                ['slug' => 'test-1', 'title' => 'Test 1', 'language' => 'pl', 'content' => 'Tresc', 'status' => 'draft', 'headline_image_enabled' => true],
                ['slug' => 'test-2', 'title' => 'Test 2', 'language' => 'pl', 'content' => 'Tresc', 'status' => 'draft', 'headline_image_enabled' => true],
            ],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Import file must contain exactly one article.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenArticleEntryIsNotJsonObject(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => ['invalid'],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Element article[0] must be an array.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenExcerptHasInvalidType(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => 123,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field excerpt must be a string or null.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenHeadlineImageHasInvalidType(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => ['invalid'],
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field headline_image must be a string or null.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenHeadlineImageEnabledIsNotBoolean(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => 'yes',
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].headline_image_enabled must be true or false.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenTableOfContentsEnabledIsNotBoolean(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'table_of_contents_enabled' => 'yes',
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].table_of_contents_enabled must be true or false.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenTableOfContentsEnabledIsNull(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'table_of_contents_enabled' => null,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].table_of_contents_enabled must be true or false.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPublishedAtHasInvalidType(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => 123,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].published_at must be an ISO-8601 string or null.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPublishedAtIsMalformed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => null,
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => 'not-a-date',
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('Field article[0].published_at does not contain a valid date.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenHeadlineImageFailsValidation(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Tytul',
                'language' => 'pl',
                'slug' => 'testowy-slug',
                'excerpt' => null,
                'headline_image' => 'ftp://example.com/image.png',
                'headline_image_enabled' => true,
                'content' => 'Tresc',
                'status' => 'draft',
                'published_at' => null,
            ]],
        ]);

        $this->expectException(ArticleImportException::class);
        $this->expectExceptionMessage('headlineImage: must start with http://, https://, or /.');

        $processor->process($queueItem);
    }

    public function testProcessDoesNotMutateExistingArticleWhenValidationFails(): void
    {
        $existingArticle = (new Article())
            ->setTitle('Stary tytul')
            ->setLanguage(ArticleLanguage::PL)
            ->setSlug('importowany-artykul')
            ->setExcerpt('Stary opis')
            ->setHeadlineImage('/assets/img/old.png')
            ->setHeadlineImageEnabled(true)
            ->setTableOfContentsEnabled(true)
            ->setContent('Stara tresc')
            ->setStatus(ArticleStatus::DRAFT)
            ->setPublishedAt(null);
        $this->setEntityId($existingArticle, 10);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock($existingArticle, []);
        $processor = $this->createProcessor($entityManager, $repository);
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'article-export',
            'version' => 1,
            'article' => [[
                'title' => 'Nowy tytul',
                'language' => 'en',
                'slug' => 'importowany-artykul',
                'excerpt' => 'Nowy opis',
                'headline_image' => 'ftp://example.com/image.png',
                'headline_image_enabled' => false,
                'table_of_contents_enabled' => false,
                'content' => 'Nowa tresc',
                'status' => 'published',
                'published_at' => '2026-03-23T10:00:00+00:00',
            ]],
        ]);

        try {
            $processor->process($queueItem);
            self::fail('Expected import validation to fail.');
        } catch (ArticleImportException $exception) {
            $this->assertStringContainsString('headlineImage: must start with http://, https://, or /.', $exception->getMessage());
        }

        $this->assertSame('Stary tytul', $existingArticle->getTitle());
        $this->assertSame(ArticleLanguage::PL, $existingArticle->getLanguage());
        $this->assertSame('Stary opis', $existingArticle->getExcerpt());
        $this->assertSame('/assets/img/old.png', $existingArticle->getHeadlineImage());
        $this->assertTrue($existingArticle->isHeadlineImageEnabled());
        $this->assertTrue($existingArticle->isTableOfContentsEnabled());
        $this->assertSame('Stara tresc', $existingArticle->getContent());
        $this->assertSame(ArticleStatus::DRAFT, $existingArticle->getStatus());
        $this->assertNull($existingArticle->getPublishedAt());
    }

    private function createProcessor(EntityManagerInterface $entityManager, ArticleRepository $repository): ArticleImportProcessor
    {
        return new ArticleImportProcessor(
            $repository,
            new ArticlePublisher($repository, new ArticleSlugger()),
            $this->createValidator(),
            $entityManager,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports'),
        );
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createQueueItemWithPayload(array $payload): ArticleImportQueue
    {
        $fileName = 'import-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents(
            $this->projectDir.'/var/imports/'.$fileName,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return (new ArticleImportQueue())
            ->setOriginalFilename($fileName)
            ->setFilePath('var/imports/'.$fileName);
    }

    private function createQueueItemWithRawContents(string $contents): ArticleImportQueue
    {
        $fileName = 'import-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($this->projectDir.'/var/imports/'.$fileName, $contents);

        return $this->createQueueItemWithFilePath('var/imports/'.$fileName);
    }

    private function createQueueItemWithFilePath(string $filePath): ArticleImportQueue
    {
        return (new ArticleImportQueue())
            ->setOriginalFilename(basename($filePath))
            ->setFilePath($filePath);
    }

    /**
     * @param list<string> $existingSlugs
     */
    private function createRepositoryMock(?Article $existingArticle, array $existingSlugs): ArticleRepository
    {
        /** @var ArticleRepository&MockObject $repository */
        $repository = $this->createMock(ArticleRepository::class);
        $repository
            ->method('findOneBySlug')
            ->willReturnCallback(static fn (string $slug): ?Article => null !== $existingArticle && $existingArticle->getSlug() === $slug ? $existingArticle : null);
        $repository
            ->method('slugExists')
            ->willReturnCallback(
                static fn (string $slug, ?int $ignoreId = null): bool => in_array($slug, $existingSlugs, true)
            );

        return $repository;
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

    private function setEntityId(Article $article, int $id): void
    {
        $reflection = new \ReflectionProperty($article, 'id');
        $reflection->setValue($article, $id);
    }

    private function setArticleCreatedAt(Article $article, \DateTimeImmutable $createdAt): void
    {
        $reflection = new \ReflectionProperty($article, 'createdAt');
        $reflection->setValue($article, $createdAt);
    }
}
