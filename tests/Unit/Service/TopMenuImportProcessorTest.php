<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\TopMenuImportQueue;
use App\Entity\TopMenuItem;
use App\Exception\TopMenuImportException;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Repository\TopMenuItemRepository;
use App\Service\ManagedFilePathResolver;
use App\Service\TopMenuImportProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TopMenuImportProcessorTest extends TestCase
{
    public function testProcessImportsParentBeforeChildEvenWhenFileOrderIsReversed(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'child',
                        'parent_unique_name' => 'parent',
                        'labels' => ['pl' => 'Child'],
                        'target_type' => 'blog_home',
                        'position' => 2,
                        'status' => 'active',
                    ],
                    [
                        'unique_name' => 'parent',
                        'parent_unique_name' => null,
                        'labels' => ['pl' => 'Parent'],
                        'target_type' => 'blog_home',
                        'position' => 1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['child', 'parent'])
                ->willReturn([]);
            $repository
                ->expects($this->never())
                ->method('findOneByUniqueName');

            $persistedItems = [];
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager
                ->expects($this->exactly(2))
                ->method('persist')
                ->willReturnCallback(static function (TopMenuItem $item) use (&$persistedItems): void {
                    $persistedItems[] = $item;
                });

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList());

            $pathResolver = new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports');

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $entityManager,
                $pathResolver,
            );

            $processedCount = $processor->process($queueItem);

            $this->assertSame(2, $processedCount);
            $this->assertCount(2, $persistedItems);
            $this->assertSame('parent', $persistedItems[0]->getUniqueName());
            $this->assertSame('child', $persistedItems[1]->getUniqueName());
            $this->assertSame($persistedItems[0], $persistedItems[1]->getParent());
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessFetchesMissingDatabaseParentsInBulkBeforeSorting(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'child',
                        'parent_unique_name' => 'existing-parent',
                        'labels' => ['pl' => 'Child'],
                        'target_type' => 'blog_home',
                        'position' => 2,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $existingParent = (new TopMenuItem())
                ->setUniqueName('existing-parent')
                ->setLabels(['pl' => 'Existing parent']);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->exactly(2))
                ->method('findByUniqueNames')
                ->willReturnCallback(static function (array $uniqueNames) use ($existingParent): array {
                    sort($uniqueNames);

                    return match ($uniqueNames) {
                        ['child'] => [],
                        ['existing-parent'] => ['existing-parent' => $existingParent],
                        default => throw new \RuntimeException('Unexpected unique names lookup.'),
                    };
                });
            $repository
                ->expects($this->never())
                ->method('findOneByUniqueName');

            $persistedItems = [];
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager
                ->expects($this->once())
                ->method('persist')
                ->willReturnCallback(static function (TopMenuItem $item) use (&$persistedItems): void {
                    $persistedItems[] = $item;
                });

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList());

            $pathResolver = new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports');

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $entityManager,
                $pathResolver,
            );

            $processedCount = $processor->process($queueItem);

            $this->assertSame(1, $processedCount);
            $this->assertCount(1, $persistedItems);
            $this->assertSame($existingParent, $persistedItems[0]->getParent());
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessRejectsNegativePositionWithReadableContext(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'parent',
                        'parent_unique_name' => null,
                        'labels' => ['pl' => 'Parent'],
                        'target_type' => 'blog_home',
                        'position' => -1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['parent'])
                ->willReturn([]);

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList());

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $this->createMock(EntityManagerInterface::class),
                new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports'),
            );

            $this->expectException(TopMenuImportException::class);
            $this->expectExceptionMessage('Pole menu_items[0].position musi być liczbą całkowitą większą lub równą zero.');

            $processor->process($queueItem);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessPreservesOriginalPayloadIndexInValidationErrorsAfterHierarchySorting(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'child',
                        'parent_unique_name' => 'parent',
                        'labels' => ['pl' => 'Child'],
                        'target_type' => 'blog_home',
                        'position' => -1,
                        'status' => 'active',
                    ],
                    [
                        'unique_name' => 'parent',
                        'parent_unique_name' => null,
                        'labels' => ['pl' => 'Parent'],
                        'target_type' => 'blog_home',
                        'position' => 1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['child', 'parent'])
                ->willReturn([]);

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList());

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $this->createMock(EntityManagerInterface::class),
                new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports'),
            );

            $this->expectException(TopMenuImportException::class);
            $this->expectExceptionMessage('Pole menu_items[0].position musi być liczbą całkowitą większą lub równą zero.');

            $processor->process($queueItem);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessRejectsNonStringParentUniqueNameBeforeHierarchySorting(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'child',
                        'parent_unique_name' => ['invalid'],
                        'labels' => ['pl' => 'Child'],
                        'target_type' => 'blog_home',
                        'position' => 1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['child'])
                ->willReturn([]);

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList());

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $this->createMock(EntityManagerInterface::class),
                new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports'),
            );

            $this->expectException(TopMenuImportException::class);
            $this->expectExceptionMessage('Pole menu_items[0].parent_unique_name musi być tekstem albo null.');

            $processor->process($queueItem);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessPrefixesValidationErrorsWithPayloadIndexAndUniqueName(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'contact',
                        'parent_unique_name' => null,
                        'labels' => ['pl' => 'Kontakt'],
                        'target_type' => 'blog_home',
                        'position' => 1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['contact'])
                ->willReturn([]);

            $violation = $this->createMock(ConstraintViolationInterface::class);
            $violation
                ->method('getPropertyPath')
                ->willReturn('uniqueName');
            $violation
                ->method('getMessage')
                ->willReturn('validation_top_menu_unique_name_too_long');

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList([$violation]));

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $this->createMock(EntityManagerInterface::class),
                new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports'),
            );

            $this->expectException(TopMenuImportException::class);
            $this->expectExceptionMessage('menu_items[0] (contact): uniqueName: unique_name może mieć maksymalnie 255 znaków.');

            $processor->process($queueItem);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testProcessNormalizesExternalUrlValidationMessages(): void
    {
        $projectDir = sys_get_temp_dir().'/top-menu-import-processor-'.bin2hex(random_bytes(4));
        mkdir($projectDir, 0775, true);
        mkdir($projectDir.'/var/imports', 0775, true);

        try {
            $relativePath = 'var/imports/top-menu-import.json';
            file_put_contents($projectDir.'/'.$relativePath, json_encode([
                'format' => 'top-menu-export',
                'version' => 1,
                'menu_items' => [
                    [
                        'unique_name' => 'contact',
                        'parent_unique_name' => null,
                        'labels' => ['pl' => 'Kontakt'],
                        'target_type' => 'external_url',
                        'external_url' => str_repeat('a', 501),
                        'position' => 1,
                        'status' => 'active',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename('top-menu-import.json')
                ->setFilePath($relativePath);

            $repository = $this->createMock(TopMenuItemRepository::class);
            $repository
                ->expects($this->once())
                ->method('findByUniqueNames')
                ->with(['contact'])
                ->willReturn([]);

            $violation = $this->createMock(ConstraintViolationInterface::class);
            $violation
                ->method('getPropertyPath')
                ->willReturn('externalUrl');
            $violation
                ->method('getMessage')
                ->willReturn('validation_top_menu_external_url_too_long');

            $validator = $this->createMock(ValidatorInterface::class);
            $validator
                ->method('validate')
                ->willReturn(new ConstraintViolationList([$violation]));

            $processor = new TopMenuImportProcessor(
                $repository,
                $this->createMock(ArticleCategoryRepository::class),
                $this->createMock(ArticleRepository::class),
                $validator,
                $this->createMock(EntityManagerInterface::class),
                new ManagedFilePathResolver($projectDir, 'var/exports', 'var/imports'),
            );

            $this->expectException(TopMenuImportException::class);
            $this->expectExceptionMessage('menu_items[0] (contact): externalUrl: adres URL może mieć maksymalnie 500 znaków.');

            $processor->process($queueItem);
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
