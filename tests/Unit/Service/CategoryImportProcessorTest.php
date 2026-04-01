<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\ArticleCategory;
use App\Entity\CategoryImportQueue;
use App\Enum\ArticleCategoryStatus;
use App\Exception\CategoryImportException;
use App\Repository\ArticleCategoryRepository;
use App\Service\CategoryImportProcessor;
use App\Service\ManagedFilePathResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CategoryImportProcessorTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/category-import-processor-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/var/imports', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testProcessCreatesNewCategoryFromValidExport(): void
    {
        $capturedCategory = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleCategory $category) use (&$capturedCategory): void {
                $capturedCategory = $category;
            });

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [[
                'name' => 'AI',
                'slug' => 'ai',
                'short_description' => 'Opis',
                'titles' => ['pl' => 'AI', 'en' => 'AI'],
                'descriptions' => ['pl' => 'Opis PL', 'en' => 'Description EN'],
                'icon' => 'ph ph-cpu',
                'status' => 'active',
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertInstanceOf(ArticleCategory::class, $capturedCategory);
        $this->assertSame('AI', $capturedCategory->getName());
        $this->assertSame('ai', $capturedCategory->getSlug());
        $this->assertSame(ArticleCategoryStatus::ACTIVE, $capturedCategory->getStatus());
    }

    public function testProcessUpdatesExistingCategoryMatchedBySlug(): void
    {
        $originalCreatedAt = new \DateTimeImmutable('2026-03-10 08:00:00', new \DateTimeZone('UTC'));
        $existingCategory = (new ArticleCategory())
            ->setName('Stara')
            ->setSlug('ai')
            ->setStatus(ArticleCategoryStatus::INACTIVE);
        $this->setEntityId($existingCategory, 10);
        $this->setCategoryCreatedAt($existingCategory, $originalCreatedAt);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock($existingCategory));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [[
                'name' => 'Nowa',
                'slug' => 'ai',
                'short_description' => null,
                'titles' => ['pl' => 'Nowa PL'],
                'descriptions' => ['pl' => 'Nowy opis'],
                'icon' => null,
                'status' => 'active',
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertSame('Nowa', $existingCategory->getName());
        $this->assertSame(ArticleCategoryStatus::ACTIVE, $existingCategory->getStatus());
        $this->assertSame(
            $originalCreatedAt->format(\DateTimeInterface::ATOM),
            $existingCategory->getCreatedAt()->format(\DateTimeInterface::ATOM)
        );
    }

    public function testProcessThrowsReadableErrorWhenStatusIsNotAllowed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [[
                'name' => 'AI',
                'slug' => 'ai',
                'short_description' => null,
                'titles' => ['pl' => 'AI'],
                'descriptions' => ['pl' => 'Opis'],
                'icon' => null,
                'status' => 'draft',
            ]],
        ]);

        $this->expectException(CategoryImportException::class);
        $this->expectExceptionMessage('Allowed values: active, inactive');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPayloadContainsDuplicateSlugs(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [
                [
                    'name' => 'AI',
                    'slug' => 'ai',
                    'short_description' => null,
                    'titles' => ['pl' => 'AI'],
                    'descriptions' => ['pl' => 'Opis'],
                    'icon' => null,
                    'status' => 'active',
                ],
                [
                    'name' => 'AI 2',
                    'slug' => 'ai',
                    'short_description' => null,
                    'titles' => ['pl' => 'AI 2'],
                    'descriptions' => ['pl' => 'Opis 2'],
                    'icon' => null,
                    'status' => 'active',
                ],
            ],
        ]);

        $this->expectException(CategoryImportException::class);
        $this->expectExceptionMessage('Field categories[1].slug duplicates value from categories[0].slug.');

        $processor->process($queueItem);
    }

    public function testProcessThrowsReadableErrorWhenPayloadContainsDuplicateNames(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [
                [
                    'name' => 'AI',
                    'slug' => 'ai',
                    'short_description' => null,
                    'titles' => ['pl' => 'AI'],
                    'descriptions' => ['pl' => 'Opis'],
                    'icon' => null,
                    'status' => 'active',
                ],
                [
                    'name' => 'AI',
                    'slug' => 'ai-2',
                    'short_description' => null,
                    'titles' => ['pl' => 'AI 2'],
                    'descriptions' => ['pl' => 'Opis 2'],
                    'icon' => null,
                    'status' => 'active',
                ],
            ],
        ]);

        $this->expectException(CategoryImportException::class);
        $this->expectExceptionMessage('Field categories[1].name duplicates value from categories[0].name.');

        $processor->process($queueItem);
    }

    public function testProcessUpdatesExistingCategoryWhenUniqueEntityValidatorReportsCurrentSlugAsTaken(): void
    {
        $existingCategory = (new ArticleCategory())
            ->setName('AI')
            ->setSlug('ai')
            ->setStatus(ArticleCategoryStatus::INACTIVE);
        $this->setEntityId($existingCategory, 15);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock($existingCategory);
        $repository
            ->method('nameExists')
            ->willReturn(false);
        $repository
            ->method('slugExists')
            ->willReturn(false);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('Ta nazwa kategorii jest już zajęta.', '', [], null, 'name', 'AI', null, UniqueEntity::NOT_UNIQUE_ERROR),
                new ConstraintViolation('Slug kategorii jest już zajęty.', '', [], null, 'slug', 'ai', null, UniqueEntity::NOT_UNIQUE_ERROR),
            ]));

        $processor = new CategoryImportProcessor(
            $repository,
            $validator,
            $entityManager,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports'),
        );

        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [[
                'name' => 'AI',
                'slug' => 'ai',
                'short_description' => 'Updated',
                'titles' => ['pl' => 'AI'],
                'descriptions' => ['pl' => 'Updated description'],
                'icon' => null,
                'status' => 'active',
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertSame(ArticleCategoryStatus::ACTIVE, $existingCategory->getStatus());
        $this->assertSame('Updated', $existingCategory->getShortDescription());
    }

    public function testProcessNormalizesPolishValidatorMessagesToEnglish(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $repository = $this->createRepositoryMock(null);
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('Nazwa kategorii może mieć maksymalnie 120 znaków.', '', [], null, 'name', str_repeat('a', 121)),
            ]));

        $processor = new CategoryImportProcessor(
            $repository,
            $validator,
            $entityManager,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports'),
        );

        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'categories' => [[
                'name' => str_repeat('a', 121),
                'slug' => 'ai',
                'short_description' => null,
                'titles' => ['pl' => 'AI'],
                'descriptions' => ['pl' => 'Opis'],
                'icon' => null,
                'status' => 'active',
            ]],
        ]);

        $this->expectException(CategoryImportException::class);
        $this->expectExceptionMessage('name: Category name can be at most 120 characters long.');

        $processor->process($queueItem);
    }

    public function testProcessSupportsLegacySingularCategoryKey(): void
    {
        $capturedCategory = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (ArticleCategory $category) use (&$capturedCategory): void {
                $capturedCategory = $category;
            });

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'category' => [[
                'name' => 'Legacy key',
                'slug' => 'legacy-key',
                'short_description' => null,
                'titles' => ['pl' => 'Legacy key'],
                'descriptions' => ['pl' => 'Legacy description'],
                'icon' => null,
                'status' => 'active',
            ]],
        ]);

        $importedCount = $processor->process($queueItem);

        $this->assertSame(1, $importedCount);
        $this->assertInstanceOf(ArticleCategory::class, $capturedCategory);
        $this->assertSame('legacy-key', $capturedCategory->getSlug());
    }

    public function testProcessUsesLegacyPayloadKeyInErrorMessages(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $processor = $this->createProcessor($entityManager, $this->createRepositoryMock(null));
        $queueItem = $this->createQueueItemWithPayload([
            'format' => 'category-export',
            'version' => 1,
            'category' => [
                [
                    'name' => 'Legacy key',
                    'slug' => 'legacy-key',
                    'short_description' => null,
                    'titles' => ['pl' => 'Legacy key'],
                    'descriptions' => ['pl' => 'Legacy description'],
                    'icon' => null,
                    'status' => 'active',
                ],
                [
                    'name' => 'Legacy key',
                    'slug' => 'legacy-key-2',
                    'short_description' => null,
                    'titles' => ['pl' => 'Legacy key 2'],
                    'descriptions' => ['pl' => 'Legacy description 2'],
                    'icon' => null,
                    'status' => 'active',
                ],
            ],
        ]);

        $this->expectException(CategoryImportException::class);
        $this->expectExceptionMessage('Field category[1].name duplicates value from category[0].name.');

        $processor->process($queueItem);
    }

    private function createProcessor(
        EntityManagerInterface $entityManager,
        ArticleCategoryRepository $repository,
    ): CategoryImportProcessor {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        return new CategoryImportProcessor(
            $repository,
            $validator,
            $entityManager,
            new ManagedFilePathResolver($this->projectDir, 'var/exports', 'var/imports'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createQueueItemWithPayload(array $payload): CategoryImportQueue
    {
        $relativePath = 'var/imports/category-import.json';
        file_put_contents(
            $this->projectDir.'/'.$relativePath,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return (new CategoryImportQueue())
            ->setOriginalFilename('category-import.json')
            ->setFilePath($relativePath);
    }

    private function createRepositoryMock(?ArticleCategory $category): ArticleCategoryRepository
    {
        /** @var ArticleCategoryRepository&MockObject $repository */
        $repository = $this->createMock(ArticleCategoryRepository::class);
        $repository
            ->method('findOneBy')
            ->willReturn($category);
        $repository
            ->method('nameExists')
            ->willReturn(false);
        $repository
            ->method('slugExists')
            ->willReturn(false);

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

            unlink($path);
        }

        rmdir($directory);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

    private function setCategoryCreatedAt(ArticleCategory $category, \DateTimeImmutable $createdAt): void
    {
        $reflectionProperty = new \ReflectionProperty($category, 'createdAt');
        $reflectionProperty->setValue($category, $createdAt);
    }
}
