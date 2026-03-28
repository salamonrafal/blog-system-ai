<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleController;
use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\BlogSettings;
use App\Entity\User;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleRepository;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ArticleControllerTest extends TestCase
{
    public function testIndexBuildsPaginatedArticleListUsingDedicatedAdminSetting(): void
    {
        $settings = (new BlogSettings())
            ->setAdminArticlesPerPage(25);
        $articles = [
            (new Article())->setTitle('Article 1')->setSlug('article-1'),
            (new Article())->setTitle('Article 2')->setSlug('article-2'),
        ];
        $categories = [
            (new ArticleCategory())->setName('PHP'),
            (new ArticleCategory())->setName('AI'),
        ];

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null)
            ->willReturn(63);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedOrderedByCreatedDate')
            ->with(2, 25, null)
            ->willReturn($articles);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn($categories);
        $categoryRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();
        $paginationBuilder = new PaginationBuilder();

        $response = $controller->index(new Request(['page' => '2']), $articleRepository, $categoryRepository, $blogSettingsProvider, $paginationBuilder);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article/index.html.twig', $controller->capturedView);
        $this->assertSame($articles, $controller->capturedParameters['articles']);
        $this->assertSame($categories, $controller->capturedParameters['article_categories']);
        $this->assertNull($controller->capturedParameters['selected_category']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame(2, $controller->capturedParameters['current_page']);
        $this->assertSame(3, $controller->capturedParameters['total_pages']);
        $this->assertSame([1, 2, 3], $controller->capturedParameters['pagination_items']);
    }

    public function testIndexKeepsPaginationStateConsistentWhenThereAreNoArticles(): void
    {
        $settings = (new BlogSettings())
            ->setAdminArticlesPerPage(25);
        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedOrderedByCreatedDate')
            ->with(1, 25, null)
            ->willReturn([]);

        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);
        $categoryRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();
        $paginationBuilder = new PaginationBuilder();

        $response = $controller->index(new Request(), $articleRepository, $categoryRepository, $blogSettingsProvider, $paginationBuilder);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(1, $controller->capturedParameters['current_page']);
        $this->assertSame(1, $controller->capturedParameters['total_pages']);
        $this->assertSame([1], $controller->capturedParameters['pagination_items']);
    }

    public function testIndexFiltersArticlesBySelectedCategory(): void
    {
        $settings = (new BlogSettings())
            ->setAdminArticlesPerPage(10);
        $selectedCategory = (new ArticleCategory())->setName('AI');
        $this->setEntityId($selectedCategory, 7);
        $article = (new Article())
            ->setTitle('AI article')
            ->setSlug('ai-article')
            ->setCategory($selectedCategory);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with($selectedCategory)
            ->willReturn(1);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedOrderedByCreatedDate')
            ->with(1, 10, $selectedCategory)
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(7)
            ->willReturn($selectedCategory);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([$selectedCategory]);

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['category' => '7']),
            $articleRepository,
            $categoryRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame($selectedCategory, $controller->capturedParameters['selected_category']);
        $this->assertSame(['category' => 7], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame([$article], $controller->capturedParameters['articles']);
    }

    public function testIndexTreatsEmptyCategoryFilterAsNoFilter(): void
    {
        $settings = (new BlogSettings())
            ->setAdminArticlesPerPage(10);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedOrderedByCreatedDate')
            ->with(1, 10, null)
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $response = $controller->index(
            new Request(['category' => '']),
            $articleRepository,
            $categoryRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($controller->capturedParameters['selected_category']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
    }

    public function testAssignToMeSetsCurrentUserAsAuthorWhenArticleHasNoAuthor(): void
    {
        $currentUser = (new User())
            ->setEmail('author@example.com')
            ->setPassword('hashed-password');
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->assignToMe($article, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame($currentUser, $article->getCreatedBy());
        $this->assertSame($currentUser, $article->getUpdatedBy());
        $this->assertSame([['success', 'Autor artykułu został przypisany.']], $controller->flashes);
    }

    public function testAssignToMeDoesNotOverwriteExistingAuthor(): void
    {
        $existingAuthor = (new User())
            ->setEmail('existing@example.com')
            ->setPassword('hashed-password');
        $currentUser = (new User())
            ->setEmail('current@example.com')
            ->setPassword('hashed-password');
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article')
            ->setCreatedBy($existingAuthor);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('flush');
        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->assignToMe($article, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame($existingAuthor, $article->getCreatedBy());
        $this->assertSame([['error', 'Artykuł ma już przypisanego autora.']], $controller->flashes);
    }

    public function testExportAddsArticleToQueueWhenRepositoryEnqueuesIt(): void
    {
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article');
        $this->setEntityId($article, 10);
        $currentUser = (new User())
            ->setEmail('exporter@example.com')
            ->setFullName('Eksporter');

        $queueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('enqueuePending')
            ->with($article, $currentUser)
            ->willReturn(true);

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->export($article, $request, $queueRepository);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame([['success', 'Article export added to the queue.']], $controller->flashes);
    }

    public function testExportReportsAlreadyQueuedWhenAtomicEnqueueRejectsDuplicate(): void
    {
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article');
        $this->setEntityId($article, 10);

        $queueRepository = $this->createMock(ArticleExportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('enqueuePending')
            ->with($article, null)
            ->willReturn(false);

        $controller = new TestArticleController();
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->export($article, $request, $queueRepository);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame([['success', 'Article export is already queued.']], $controller->flashes);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}

final class TestArticleController extends ArticleController
{
    public ?User $authenticatedUser = null;

    public bool $csrfTokenIsValid = true;

    public ?string $capturedView = null;

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    public function getUser(): ?User
    {
        return $this->authenticatedUser;
    }

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

        return $response ?? new Response();
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/admin/articles', $status);
    }
}
