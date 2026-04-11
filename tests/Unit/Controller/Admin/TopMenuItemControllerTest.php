<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\TopMenuItemController;
use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Entity\User;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\TopMenuCacheManager;
use App\Service\TopMenuItemUniqueNameGenerator;
use App\Service\UserLanguageResolver;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validation;

final class TopMenuItemControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsExpectedStatistics(): void
    {
        $item = (new TopMenuItem())->setLabel('pl', 'Blog');

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->expects($this->once())->method('findForAdminIndexView')->willReturn([[
            'item' => $item,
            'hasChildren' => false,
        ]]);
        $repository->expects($this->once())->method('count')->with([])->willReturn(1);
        $repository->expects($this->once())->method('countActive')->willReturn(1);
        $repository->expects($this->once())->method('countInactive')->willReturn(0);

        $controller = new TestTopMenuItemController();
        $response = $controller->index($repository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/top_menu/index.html.twig', $controller->capturedView);
        $this->assertSame(['all' => 1, 'active' => 1, 'inactive' => 0], $controller->capturedParameters['menu_stats']);
    }

    public function testNewPersistsMenuItemOnValidSubmit(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $uniqueNameGenerator = $this->createMock(TopMenuItemUniqueNameGenerator::class);
        $uniqueNameGenerator
            ->expects($this->once())
            ->method('refreshUniqueName')
            ->with($this->callback(function (TopMenuItem $item): bool {
                $item->setUniqueName('kontakt');

                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (TopMenuItem $item): bool {
                $this->assertSame('Kontakt', $item->getLabel('pl'));
                $this->assertSame('Contact', $item->getLabel('en'));
                $this->assertSame('kontakt', $item->getUniqueName());
                $this->assertSame('https://example.com/contact', $item->getExternalUrl());
                $this->assertTrue($item->isExternalUrlOpenInNewWindow());

                return true;
            }));
        $entityManager->expects($this->once())->method('flush');

        $controller = new TestTopMenuItemController();
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findRecentForTopMenuSelection')
            ->willReturn([]);
        $topMenuRepository = $this->createTopMenuRepositoryMock([]);
        $topMenuRepository
            ->expects($this->once())
            ->method('applySiblingPositioning')
            ->with($this->isInstanceOf(TopMenuItem::class));
        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager
            ->expects($this->once())
            ->method('refresh');

        $request = new Request([], [
            'top_menu_item' => [
                'labels' => ['pl' => 'Kontakt', 'en' => 'Contact'],
                'targetType' => 'external_url',
                'externalUrl' => 'https://example.com/contact',
                'externalUrlOpenInNewWindow' => '1',
                'position' => '20',
                'status' => 'active',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->new(
            $request,
            $entityManager,
            $topMenuRepository,
            $this->createMock(ArticleCategoryRepository::class),
            $articleRepository,
            $this->createUserLanguageResolverMock('en'),
            $uniqueNameGenerator,
            $topMenuCacheManager,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu', $response->getTargetUrl());
        $this->assertSame([['success', 'Menu item created.']], $controller->flashes);
    }

    public function testTreeRendersMenuHierarchyView(): void
    {
        $item = (new TopMenuItem())->setLabel('pl', 'Blog');

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository
            ->expects($this->once())
            ->method('findTreeForAdmin')
            ->willReturn([
                ['item' => $item, 'children' => []],
            ]);

        $controller = new TestTopMenuItemController();
        $response = $controller->tree($repository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/top_menu/tree.html.twig', $controller->capturedView);
        $this->assertCount(1, $controller->capturedParameters['menu_tree']);
    }

    public function testNewPrefillsParentWhenParentQueryParameterIsProvided(): void
    {
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($parent, 15);

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->method('findForAdminIndex')->willReturn([$parent]);
        $repository->expects($this->once())->method('find')->with(15)->willReturn($parent);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findRecentForTopMenuSelection')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([]);

        $controller = new TestTopMenuItemController();
        $response = $controller->new(
            new Request(['parent' => '15']),
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $categoryRepository,
            $articleRepository,
            $this->createUserLanguageResolverMock('pl'),
            $this->createMock(TopMenuItemUniqueNameGenerator::class),
            $this->createMock(TopMenuCacheManager::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/top_menu/new.html.twig', $controller->capturedView);
        $this->assertSame($parent, $controller->capturedParameters['form']->getData()->getParent());
    }

    public function testNewIgnoresParentPrefillWhenRequestedParentIsAlreadyNested(): void
    {
        $topLevelParent = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($topLevelParent, 15);

        $nestedParent = (new TopMenuItem())
            ->setLabel('pl', 'PHP')
            ->setParent($topLevelParent);
        $this->setEntityId($nestedParent, 19);

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->method('findForAdminIndex')->willReturn([$topLevelParent, $nestedParent]);
        $repository->expects($this->once())->method('find')->with(19)->willReturn($nestedParent);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findRecentForTopMenuSelection')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([]);

        $controller = new TestTopMenuItemController();
        $response = $controller->new(
            new Request(['parent' => '19']),
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $categoryRepository,
            $articleRepository,
            $this->createUserLanguageResolverMock('pl'),
            $this->createMock(TopMenuItemUniqueNameGenerator::class),
            $this->createMock(TopMenuCacheManager::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/top_menu/new.html.twig', $controller->capturedView);
        $this->assertNull($controller->capturedParameters['form']->getData()->getParent());
        $this->assertSame([$topLevelParent], $controller->capturedParameters['form']->get('parent')->getConfig()->getOption('choices'));
    }

    public function testNewPrefillsParentAndPositionWhenAfterQueryParameterIsProvided(): void
    {
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($parent, 15);

        $afterItem = (new TopMenuItem())
            ->setLabel('pl', 'Kontakt')
            ->setParent($parent)
            ->setPosition(4);
        $this->setEntityId($afterItem, 27);

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->method('findForAdminIndex')->willReturn([$parent, $afterItem]);
        $repository->expects($this->once())->method('find')->with(27)->willReturn($afterItem);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findRecentForTopMenuSelection')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([]);

        $controller = new TestTopMenuItemController();
        $response = $controller->new(
            new Request(['after' => '27']),
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $categoryRepository,
            $articleRepository,
            $this->createUserLanguageResolverMock('pl'),
            $this->createMock(TopMenuItemUniqueNameGenerator::class),
            $this->createMock(TopMenuCacheManager::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/top_menu/new.html.twig', $controller->capturedView);
        $this->assertSame($parent, $controller->capturedParameters['form']->getData()->getParent());
        $this->assertSame(5, $controller->capturedParameters['form']->getData()->getPosition());
    }

    public function testDeleteThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $item = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($item, 12);

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = false;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->delete(
            $item,
            new Request([], ['_token' => 'invalid']),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TopMenuItemRepository::class),
            $this->createUserLanguageResolverMock('pl'),
            $this->createMock(TopMenuCacheManager::class),
        );
    }

    public function testDeleteBlocksRemovingMenuItemThatStillHasChildren(): void
    {
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($parent, 12);

        $child = (new TopMenuItem())->setLabel('pl', 'PHP');
        $parent->addChild($child);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');
        $entityManager->expects($this->never())->method('flush');

        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuRepository->expects($this->never())->method('normalizeSiblingPositions');

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->never())->method('refresh');

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->delete(
            $parent,
            new Request([], ['_token' => 'valid']),
            $entityManager,
            $topMenuRepository,
            $this->createUserLanguageResolverMock('en'),
            $topMenuCacheManager,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu', $response->getTargetUrl());
        $this->assertSame([['error', 'You cannot delete a menu item that still has children.']], $controller->flashes);
    }

    public function testDeleteRedirectsBackToTreeWhenRequestedFromTreeView(): void
    {
        $item = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($item, 12);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($item);
        $entityManager->expects($this->once())->method('flush');

        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuRepository
            ->expects($this->once())
            ->method('normalizeSiblingPositions')
            ->with(null, 12);

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->once())->method('refresh');

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->delete(
            $item,
            new Request([], ['_token' => 'valid', 'redirect_to' => 'tree']),
            $entityManager,
            $topMenuRepository,
            $this->createUserLanguageResolverMock('pl'),
            $topMenuCacheManager,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu/tree', $response->getTargetUrl());
    }

    public function testDeleteBlocksRemovingMenuItemThatStillHasChildrenFromTreeView(): void
    {
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');
        $this->setEntityId($parent, 18);

        $child = (new TopMenuItem())->setLabel('pl', 'PHP');
        $parent->addChild($child);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');
        $entityManager->expects($this->never())->method('flush');

        $topMenuRepository = $this->createMock(TopMenuItemRepository::class);
        $topMenuRepository->expects($this->never())->method('normalizeSiblingPositions');

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->never())->method('refresh');

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->delete(
            $parent,
            new Request([], ['_token' => 'valid', 'redirect_to' => 'tree']),
            $entityManager,
            $topMenuRepository,
            $this->createUserLanguageResolverMock('pl'),
            $topMenuCacheManager,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu/tree', $response->getTargetUrl());
        $this->assertSame([['error', 'Nie można usunąć elementu menu, który ma dzieci.']], $controller->flashes);
    }

    public function testEditClearsStaleTargetRelationsWhenTargetTypeChanges(): void
    {
        $category = (new ArticleCategory())->setName('AI')->setSlug('ai');
        $this->setEntityId($category, 21);
        $article = (new Article())->setTitle('Hello')->setSlug('hello');
        $this->setEntityId($article, 31);

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'AI', 'en' => 'AI'])
            ->setUniqueName('ai')
            ->setTargetType(\App\Enum\TopMenuItemTargetType::ARTICLE_CATEGORY)
            ->setArticleCategory($category)
            ->setArticle($article)
            ->setExternalUrl('https://example.com/stale')
            ->setExternalUrlOpenInNewWindow(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $uniqueNameGenerator = $this->createMock(TopMenuItemUniqueNameGenerator::class);
        $uniqueNameGenerator
            ->expects($this->never())
            ->method('refreshUniqueName')
        ;

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findRecentForTopMenuSelection')
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([$category]);

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->once())->method('refresh');

        $request = new Request([], [
            'top_menu_item' => [
                'labels' => ['pl' => 'Blog', 'en' => 'Blog'],
                'targetType' => 'blog_home',
                'externalUrl' => 'https://example.com/stale',
                'externalUrlOpenInNewWindow' => '1',
                'position' => '1',
                'status' => 'active',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestTopMenuItemController();
        $topMenuRepository = $this->createTopMenuRepositoryMock([]);
        $topMenuRepository
            ->expects($this->once())
            ->method('applySiblingPositioning')
            ->with($menuItem, null, false);
        $response = $controller->edit(
            $menuItem,
            $request,
            $entityManager,
            $topMenuRepository,
            $categoryRepository,
            $articleRepository,
            $this->createUserLanguageResolverMock('pl'),
            $uniqueNameGenerator,
            $topMenuCacheManager,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(\App\Enum\TopMenuItemTargetType::BLOG_HOME, $menuItem->getTargetType());
        $this->assertSame('ai', $menuItem->getUniqueName());
        $this->assertNull($menuItem->getExternalUrl());
        $this->assertFalse($menuItem->isExternalUrlOpenInNewWindow());
        $this->assertNull($menuItem->getArticleCategory());
        $this->assertNull($menuItem->getArticle());
    }

    public function testRefreshUniqueNameIfMissingSkipsGenerationWhenLabelSourceIsMissing(): void
    {
        $controller = new TestTopMenuItemController();
        $menuItem = new TopMenuItem();
        $uniqueNameGenerator = $this->createMock(TopMenuItemUniqueNameGenerator::class);
        $uniqueNameGenerator->expects($this->never())->method('refreshUniqueName');

        $invoker = \Closure::bind(
            static function (TopMenuItemController $controller, TopMenuItem $menuItem, TopMenuItemUniqueNameGenerator $uniqueNameGenerator): void {
                $controller->refreshUniqueNameIfMissing($menuItem, $uniqueNameGenerator);
            },
            null,
            TopMenuItemController::class
        );

        $invoker($controller, $menuItem, $uniqueNameGenerator);
    }

    public function testExportQueuesWholeTopMenuHierarchy(): void
    {
        $queueRepository = $this->createMock(TopMenuExportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('enqueuePending')
            ->with(null)
            ->willReturn(true);

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->export(
            new Request([], ['_token' => 'valid']),
            $queueRepository,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu', $response->getTargetUrl());
        $this->assertSame([['success', 'Eksport top menu został dodany do kolejki.']], $controller->flashes);
    }

    public function testReorderPersistsNewSiblingOrder(): void
    {
        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository
            ->expects($this->once())
            ->method('reorderSiblings')
            ->with(null, [3, 1, 2])
            ->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->once())->method('refresh');

        $request = new Request([], [], [], [], [], [], json_encode([
            'parentId' => null,
            'orderedIds' => [3, 1, 2],
        ], \JSON_THROW_ON_ERROR));
        $request->headers->set('X-CSRF-Token', 'valid');

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->reorder(
            $request,
            $repository,
            $entityManager,
            $this->createUserLanguageResolverMock('pl'),
            $topMenuCacheManager,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('Nowa kolejność elementów menu została zapisana.', $payload['message'] ?? null);
    }

    public function testReorderReturnsBadRequestWhenPayloadDoesNotMatchSiblingBranch(): void
    {
        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository
            ->expects($this->once())
            ->method('reorderSiblings')
            ->with(7, [3, 1, 2])
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $topMenuCacheManager = $this->createMock(TopMenuCacheManager::class);
        $topMenuCacheManager->expects($this->never())->method('refresh');

        $request = new Request([], [], [], [], [], [], json_encode([
            'parentId' => 7,
            'orderedIds' => [3, 1, 2],
        ], \JSON_THROW_ON_ERROR));
        $request->headers->set('X-CSRF-Token', 'valid');

        $controller = new TestTopMenuItemController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->reorder(
            $request,
            $repository,
            $entityManager,
            $this->createUserLanguageResolverMock('en'),
            $topMenuCacheManager,
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('The new menu item order could not be saved.', $payload['message'] ?? null);
    }

    /**
     * @param list<TopMenuItem> $items
     */
    private function createTopMenuRepositoryMock(array $items): TopMenuItemRepository
    {
        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->method('findForAdminIndex')->willReturn($items);

        return $repository;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}

final class TestTopMenuItemController extends TopMenuItemController
{
    public bool $csrfTokenIsValid = true;

    public ?User $authenticatedUser = null;

    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenIsValid;
    }

    public function getUser(): ?User
    {
        return $this->authenticatedUser;
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
        return new RedirectResponse(match ($route) {
            'admin_top_menu_tree' => '/admin/top-menu/tree',
            default => '/admin/top-menu',
        }, $status);
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $validator = Validation::createValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
