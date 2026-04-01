<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\QueueStatusController;
use App\Entity\Article;
use App\Entity\ArticleExportQueue;
use App\Entity\CategoryExportQueue;
use App\Entity\ArticleCategory;
use App\Entity\ArticleImportQueue;
use App\Entity\CategoryImportQueue;
use App\Entity\TopMenuImportQueue;
use App\Entity\TopMenuExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\CategoryImportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedFilePathResolver;
use App\Service\UserLanguageResolver;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class QueueStatusControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsQueueOverview(): void
    {
        $exportQueueItem = new ArticleExportQueue((new Article())->setTitle('Export')->setSlug('export'));
        $categoryExportQueueItem = new CategoryExportQueue((new ArticleCategory())->setName('AI'));
        $topMenuExportQueueItem = new TopMenuExportQueue();
        $importQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('import.json')
            ->setFilePath('var/imports/import.json');
        $categoryImportQueueItem = (new CategoryImportQueue())
            ->setOriginalFilename('category-import.json')
            ->setFilePath('var/imports/category-import.json');
        $topMenuImportQueueItem = (new TopMenuImportQueue())
            ->setOriginalFilename('top-menu-import.json')
            ->setFilePath('var/imports/top-menu-import.json');
        $this->setEntityId($exportQueueItem, 11);
        $this->setEntityId($categoryExportQueueItem, 12);
        $this->setEntityId($topMenuExportQueueItem, 13);

        $exportRepository = $this->createMock(ArticleExportQueueRepository::class);
        $exportRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$exportQueueItem]);
        $exportRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(1);

        $importRepository = $this->createMock(ArticleImportQueueRepository::class);
        $importRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$importQueueItem]);
        $importRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(0);
        $categoryImportRepository = $this->createMock(CategoryImportQueueRepository::class);
        $categoryImportRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$categoryImportQueueItem]);
        $categoryImportRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(0);
        $topMenuImportRepository = $this->createMock(TopMenuImportQueueRepository::class);
        $topMenuImportRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$topMenuImportQueueItem]);
        $topMenuImportRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(0);
        $categoryExportRepository = $this->createMock(CategoryExportQueueRepository::class);
        $categoryExportRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$categoryExportQueueItem]);
        $categoryExportRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(1);
        $topMenuExportRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $topMenuExportRepository
            ->expects($this->once())
            ->method('findPendingOrderedByCreatedAt')
            ->willReturn([$topMenuExportQueueItem]);
        $topMenuExportRepository
            ->expects($this->once())
            ->method('countPending')
            ->willReturn(1);

        $controller = $this->createController();
        $response = $controller->index($exportRepository, $categoryExportRepository, $topMenuExportRepository, $importRepository, $categoryImportRepository, $topMenuImportRepository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/queue_status/index.html.twig', $controller->capturedView);
        $this->assertCount(3, $controller->capturedParameters['pending_export_queue_items']);
        $this->assertSame('delete_queue_item_'.$exportQueueItem->getId(), $controller->capturedParameters['pending_export_queue_items'][0]['csrf_token_id']);
        $this->assertSame('delete_category_export_queue_item_'.$categoryExportQueueItem->getId(), $controller->capturedParameters['pending_export_queue_items'][1]['csrf_token_id']);
        $this->assertSame('delete_top_menu_export_queue_item_'.$topMenuExportQueueItem->getId(), $controller->capturedParameters['pending_export_queue_items'][2]['csrf_token_id']);
        $this->assertCount(3, $controller->capturedParameters['pending_import_queue_items']);
        $typeKeys = array_map(
            static fn (array $item): string => $item['type_key'],
            $controller->capturedParameters['pending_import_queue_items']
        );
        sort($typeKeys);
        $this->assertSame(['admin_queue_type_article_import', 'admin_queue_type_category_import', 'admin_queue_type_top_menu_import'], $typeKeys);
        $this->assertTrue($controller->capturedParameters['has_pending_queue_items']);
    }

    public function testClearRemovesPendingItemsAndDeletesImportFiles(): void
    {
        $exportQueueItem = new ArticleExportQueue((new Article())->setTitle('Export')->setSlug('export'));
        $categoryExportQueueItem = new CategoryExportQueue((new ArticleCategory())->setName('AI'));
        $topMenuExportQueueItem = new TopMenuExportQueue();
        $importQueueItem = (new ArticleImportQueue())
            ->setOriginalFilename('import.json')
            ->setFilePath('var/imports/import.json');
        $categoryImportQueueItem = (new CategoryImportQueue())
            ->setOriginalFilename('category-import.json')
            ->setFilePath('var/imports/category-import.json');
        $topMenuImportQueueItem = (new TopMenuImportQueue())
            ->setOriginalFilename('top-menu-import.json')
            ->setFilePath('var/imports/top-menu-import.json');

        $exportRepository = $this->createMock(ArticleExportQueueRepository::class);
        $exportRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleExportQueueStatus::PENDING])
            ->willReturn([$exportQueueItem]);
        $categoryExportRepository = $this->createMock(CategoryExportQueueRepository::class);
        $categoryExportRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleExportQueueStatus::PENDING])
            ->willReturn([$categoryExportQueueItem]);
        $topMenuExportRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $topMenuExportRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleExportQueueStatus::PENDING])
            ->willReturn([$topMenuExportQueueItem]);

        $importRepository = $this->createMock(ArticleImportQueueRepository::class);
        $importRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleImportQueueStatus::PENDING])
            ->willReturn([$importQueueItem]);
        $categoryImportRepository = $this->createMock(CategoryImportQueueRepository::class);
        $categoryImportRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleImportQueueStatus::PENDING])
            ->willReturn([$categoryImportQueueItem]);
        $topMenuImportRepository = $this->createMock(TopMenuImportQueueRepository::class);
        $topMenuImportRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['status' => ArticleImportQueueStatus::PENDING])
            ->willReturn([$topMenuImportQueueItem]);

        $removedEntities = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(6))
            ->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removedEntities): void {
                $removedEntities[] = $entity;
            });
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $pathResolver = $this->createMock(ManagedFilePathResolver::class);
        $pathResolver
            ->expects($this->exactly(3))
            ->method('resolveImportPath')
            ->willReturnMap([
                ['var/imports/import.json', '/tmp/import.json'],
                ['var/imports/category-import.json', '/tmp/category-import.json'],
                ['var/imports/top-menu-import.json', '/tmp/top-menu-import.json'],
            ]);

        $fileDeleter = $this->createMock(ManagedFileDeleter::class);
        $fileDeleter
            ->expects($this->exactly(3))
            ->method('delete')
            ->willReturnCallback(static function (string $path, string $type): void {
                TestCase::assertSame('import', $type);
                TestCase::assertContains($path, ['/tmp/import.json', '/tmp/category-import.json', '/tmp/top-menu-import.json']);
            });

        $controller = $this->createController($pathResolver, $fileDeleter);
        $controller->csrfTokenIsValid = true;
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $response = $controller->clear(new Request([], ['_token' => 'valid']), $exportRepository, $categoryExportRepository, $topMenuExportRepository, $importRepository, $categoryImportRepository, $topMenuImportRepository, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/queues/status', $response->getTargetUrl());
        $this->assertSame([['success', 'The pending queue has been cleared.']], $controller->flashes);
        $this->assertSame([$exportQueueItem, $categoryExportQueueItem, $topMenuExportQueueItem, $importQueueItem, $categoryImportQueueItem, $topMenuImportQueueItem], $removedEntities);
    }

    public function testDeleteImportDeletesManagedFileAndQueueItem(): void
    {
        $queueItem = (new ArticleImportQueue())
            ->setOriginalFilename('import.json')
            ->setFilePath('var/imports/import.json');
        $this->setEntityId($queueItem, 15);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($queueItem);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $pathResolver = $this->createMock(ManagedFilePathResolver::class);
        $pathResolver
            ->expects($this->once())
            ->method('resolveImportPath')
            ->with('var/imports/import.json')
            ->willReturn('/tmp/import.json');

        $fileDeleter = $this->createMock(ManagedFileDeleter::class);
        $fileDeleter
            ->expects($this->once())
            ->method('delete')
            ->with('/tmp/import.json', 'import');

        $controller = $this->createController($pathResolver, $fileDeleter);
        $controller->csrfTokenIsValid = true;
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $response = $controller->deleteImport($queueItem, new Request([], ['_token' => 'valid']), $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/queues/status', $response->getTargetUrl());
        $this->assertSame([['success', 'The item has been removed from the queue.']], $controller->flashes);
    }

    public function testDeleteExportThrowsWhenCsrfTokenIsInvalid(): void
    {
        $queueItem = new ArticleExportQueue((new Article())->setTitle('Export')->setSlug('export'));
        $this->setEntityId($queueItem, 11);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');

        $controller = $this->createController();
        $controller->csrfTokenIsValid = false;
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->deleteExport($queueItem, new Request([], ['_token' => 'invalid']), $entityManager, $userLanguageResolver);
    }

    private function createController(
        ?ManagedFilePathResolver $pathResolver = null,
        ?ManagedFileDeleter $fileDeleter = null,
    ): TestQueueStatusController {
        return new TestQueueStatusController(
            $pathResolver ?? $this->createMock(ManagedFilePathResolver::class),
            $fileDeleter ?? $this->createMock(ManagedFileDeleter::class),
        );
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

}

final class TestQueueStatusController extends QueueStatusController
{
    public bool $csrfTokenIsValid = true;

    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenIsValid;
    }

    public function addFlash(string $type, mixed $message): void
    {
        $this->flashes[] = [$type, (string) $message];
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/admin/queues/status', $status);
    }
}
