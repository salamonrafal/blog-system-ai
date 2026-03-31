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
use Symfony\Contracts\Cache\CacheInterface;

final class TopMenuItemControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsExpectedStatistics(): void
    {
        $item = (new TopMenuItem())->setLabel('pl', 'Blog');

        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository->expects($this->once())->method('findForAdminIndex')->willReturn([$item]);
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
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->exactly(2))
            ->method('delete');

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
            $this->createTopMenuRepositoryMock([]),
            $this->createMock(ArticleCategoryRepository::class),
            $articleRepository,
            $this->createUserLanguageResolverMock('en'),
            $uniqueNameGenerator,
            $cache,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/top-menu', $response->getTargetUrl());
        $this->assertSame([['success', 'Menu item created.']], $controller->flashes);
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
            $this->createUserLanguageResolverMock('pl'),
            $this->createMock(CacheInterface::class),
        );
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

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->exactly(2))->method('delete');

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
        $response = $controller->edit(
            $menuItem,
            $request,
            $entityManager,
            $this->createTopMenuRepositoryMock([]),
            $categoryRepository,
            $articleRepository,
            $this->createUserLanguageResolverMock('pl'),
            $uniqueNameGenerator,
            $cache,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(\App\Enum\TopMenuItemTargetType::BLOG_HOME, $menuItem->getTargetType());
        $this->assertSame('ai', $menuItem->getUniqueName());
        $this->assertNull($menuItem->getExternalUrl());
        $this->assertFalse($menuItem->isExternalUrlOpenInNewWindow());
        $this->assertNull($menuItem->getArticleCategory());
        $this->assertNull($menuItem->getArticle());
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
        return new RedirectResponse('/admin/top-menu', $status);
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
