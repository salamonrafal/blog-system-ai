<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleController;
use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\BlogSettings;
use App\Entity\User;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleKeywordRepository;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\ArticlePublisher;
use App\Service\ArticleSlugger;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class ArticleControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsPaginatedArticleListUsingDedicatedAdminSetting(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(25);
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
            ->with(null, null, null)
            ->willReturn(63);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(2, 25, null, null, null, 'desc')
            ->willReturn($articles);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn($categories);
        $categoryRepository
            ->expects($this->never())
            ->method('find');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();
        $paginationBuilder = new PaginationBuilder();

        $response = $controller->index(new Request(['page' => '2']), $articleRepository, $categoryRepository, $userRepository, $blogSettingsProvider, $paginationBuilder);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article/index.html.twig', $controller->capturedView);
        $this->assertSame($articles, $controller->capturedParameters['articles']);
        $this->assertSame($categories, $controller->capturedParameters['article_categories']);
        $this->assertSame(ArticleStatus::cases(), $controller->capturedParameters['article_statuses']);
        $this->assertSame([], $controller->capturedParameters['article_authors']);
        $this->assertNull($controller->capturedParameters['selected_category']);
        $this->assertNull($controller->capturedParameters['selected_status']);
        $this->assertNull($controller->capturedParameters['selected_author']);
        $this->assertSame('desc', $controller->capturedParameters['sort_order']);
        $this->assertSame([], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame(2, $controller->capturedParameters['current_page']);
        $this->assertSame(3, $controller->capturedParameters['total_pages']);
        $this->assertSame([1, 2, 3], $controller->capturedParameters['pagination_items']);
    }

    public function testIndexKeepsPaginationStateConsistentWhenThereAreNoArticles(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(25);
        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 25, null, null, null, 'desc')
            ->willReturn([]);

        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);
        $categoryRepository
            ->expects($this->never())
            ->method('find');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();
        $paginationBuilder = new PaginationBuilder();

        $response = $controller->index(new Request(), $articleRepository, $categoryRepository, $userRepository, $blogSettingsProvider, $paginationBuilder);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(1, $controller->capturedParameters['current_page']);
        $this->assertSame(1, $controller->capturedParameters['total_pages']);
        $this->assertSame([1], $controller->capturedParameters['pagination_items']);
    }

    public function testIndexFiltersArticlesBySelectedCategory(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
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
            ->with($selectedCategory, null, null)
            ->willReturn(1);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, $selectedCategory, null, null, 'desc')
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

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

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
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame($selectedCategory, $controller->capturedParameters['selected_category']);
        $this->assertSame(['category' => 7], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame(['category' => 7], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame([$article], $controller->capturedParameters['articles']);
    }

    public function testIndexTreatsEmptyCategoryFilterAsNoFilter(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, null, 'desc')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

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
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($controller->capturedParameters['selected_category']);
        $this->assertNull($controller->capturedParameters['selected_status']);
        $this->assertNull($controller->capturedParameters['selected_author']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexFiltersArticlesBySelectedStatus(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $article = (new Article())
            ->setTitle('Draft article')
            ->setSlug('draft-article')
            ->setStatus(ArticleStatus::DRAFT);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, ArticleStatus::DRAFT, null)
            ->willReturn(1);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, ArticleStatus::DRAFT, null, 'desc')
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['status' => 'draft']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame(ArticleStatus::DRAFT, $controller->capturedParameters['selected_status']);
        $this->assertSame(['status' => 'draft'], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame(['status' => 'draft'], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame([$article], $controller->capturedParameters['articles']);
    }

    public function testIndexKeepsCategoryAndStatusFiltersInPaginationState(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $selectedCategory = (new ArticleCategory())->setName('AI');
        $this->setEntityId($selectedCategory, 7);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with($selectedCategory, ArticleStatus::REVIEW, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, $selectedCategory, ArticleStatus::REVIEW, null, 'desc')
            ->willReturn([]);

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

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['category' => '7', 'status' => 'review']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame($selectedCategory, $controller->capturedParameters['selected_category']);
        $this->assertSame(ArticleStatus::REVIEW, $controller->capturedParameters['selected_status']);
        $this->assertSame(['category' => 7, 'status' => 'review'], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame(['category' => 7, 'status' => 'review'], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexSortsArticlesByOldestUpdateDateWhenRequested(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $selectedCategory = (new ArticleCategory())->setName('AI');
        $this->setEntityId($selectedCategory, 7);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with($selectedCategory, ArticleStatus::REVIEW, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, $selectedCategory, ArticleStatus::REVIEW, null, 'asc')
            ->willReturn([]);

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

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['category' => '7', 'status' => 'review', 'sort' => 'asc']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame('asc', $controller->capturedParameters['sort_order']);
        $this->assertSame(['category' => 7, 'status' => 'review'], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame(['category' => 7, 'status' => 'review', 'sort' => 'asc'], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexFiltersArticlesBySelectedAuthor(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $selectedAuthor = (new User())
            ->setEmail('author@example.com')
            ->setFullName('Author Name');
        $this->setEntityId($selectedAuthor, 12);
        $article = (new Article())
            ->setTitle('Author article')
            ->setSlug('author-article')
            ->setCreatedBy($selectedAuthor);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, $selectedAuthor)
            ->willReturn(1);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, $selectedAuthor, 'desc')
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findArticleAuthorById')
            ->with(12)
            ->willReturn($selectedAuthor);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([$selectedAuthor]);

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['author' => '12']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame($selectedAuthor, $controller->capturedParameters['selected_author']);
        $this->assertSame('Author Name <author@example.com>', $controller->capturedParameters['selected_author_label']);
        $this->assertSame([
            [
                'id' => 12,
                'label' => 'Author Name <author@example.com>',
            ],
        ], $controller->capturedParameters['article_authors']);
        $this->assertSame(['author' => 12], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame(['author' => 12], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame([$article], $controller->capturedParameters['articles']);
    }

    public function testIndexPrependsSelectedAuthorWhenOutsideDefaultAuthorFilterOptions(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $selectedAuthor = (new User())
            ->setEmail('older-author@example.com');
        $defaultAuthor = (new User())
            ->setEmail('recent-author@example.com')
            ->setFullName('Recent Author');
        $this->setEntityId($selectedAuthor, 12);
        $this->setEntityId($defaultAuthor, 21);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, $selectedAuthor)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, $selectedAuthor, 'desc')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findArticleAuthorById')
            ->with(12)
            ->willReturn($selectedAuthor);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([$defaultAuthor]);

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['author' => '12']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame([
            [
                'id' => 12,
                'label' => 'older-author@example.com',
            ],
            [
                'id' => 21,
                'label' => 'Recent Author <recent-author@example.com>',
            ],
        ], $controller->capturedParameters['article_authors']);
        $this->assertSame(['author' => 12], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexKeepsAuthorFilterOptionsCappedWhenPrependingSelectedAuthor(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);
        $selectedAuthor = (new User())
            ->setEmail('selected-author@example.com')
            ->setFullName('Selected Author');
        $this->setEntityId($selectedAuthor, 12);
        $defaultAuthors = [];

        for ($i = 1; $i <= 10; ++$i) {
            $defaultAuthor = (new User())
                ->setEmail(sprintf('recent-author-%d@example.com', $i))
                ->setFullName(sprintf('Recent Author %d', $i));
            $this->setEntityId($defaultAuthor, 100 + $i);
            $defaultAuthors[] = $defaultAuthor;
        }

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, $selectedAuthor)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, $selectedAuthor, 'desc')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findArticleAuthorById')
            ->with(12)
            ->willReturn($selectedAuthor);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn($defaultAuthors);

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['author' => '12']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertCount(10, $controller->capturedParameters['article_authors']);
        $this->assertSame([
            'id' => 12,
            'label' => 'Selected Author <selected-author@example.com>',
        ], $controller->capturedParameters['article_authors'][0]);
        $this->assertSame(109, $controller->capturedParameters['article_authors'][9]['id']);
        $this->assertNotContains(110, array_column($controller->capturedParameters['article_authors'], 'id'));
    }

    public function testIndexTreatsUnsupportedStatusFilterAsNoFilter(): void
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, null, 'desc')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);
        $userRepository
            ->expects($this->never())
            ->method('find');

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            new Request(['status' => 'unsupported']),
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        $this->assertNull($controller->capturedParameters['selected_status']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexTreatsMalformedAuthorFilterAsNoFilter(): void
    {
        $controller = $this->renderArticleIndexWithEmptyAuthorFilter(new Request(['author' => 'abc']), false);

        $this->assertNull($controller->capturedParameters['selected_author']);
        $this->assertSame([], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
    }

    public function testIndexTreatsNonAuthorUserFilterAsNoFilter(): void
    {
        $controller = $this->renderArticleIndexWithEmptyAuthorFilter(new Request(['author' => '404']), true);

        $this->assertNull($controller->capturedParameters['selected_author']);
        $this->assertSame([], $controller->capturedParameters['article_filter_route_params']);
        $this->assertSame([], $controller->capturedParameters['pagination_route_params']);
    }

    public function testAuthorFilterReturnsTopMatchingAuthors(): void
    {
        $author = (new User())
            ->setEmail('author@example.com')
            ->setFullName('Author Name');
        $emailOnlyAuthor = (new User())
            ->setEmail('admin@example.com');
        $this->setEntityId($author, 12);
        $this->setEntityId($emailOnlyAuthor, 13);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('auth', 10)
            ->willReturn([$author, $emailOnlyAuthor]);

        $controller = new TestArticleController();

        $response = $controller->authorFilter(new Request(['q' => ' auth ']), $userRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'options' => [
                [
                    'id' => 12,
                    'label' => 'Author Name <author@example.com>',
                ],
                [
                    'id' => 13,
                    'label' => 'admin@example.com',
                ],
            ],
        ], json_decode((string) $response->getContent(), true));
    }

    public function testAuthorFilterKeepsSelectedAuthorForEmptySearch(): void
    {
        $selectedAuthor = (new User())
            ->setEmail('older-author@example.com')
            ->setFullName('Older Author');
        $defaultAuthor = (new User())
            ->setEmail('recent-author@example.com')
            ->setFullName('Recent Author');
        $this->setEntityId($selectedAuthor, 12);
        $this->setEntityId($defaultAuthor, 21);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findArticleAuthorById')
            ->with(12)
            ->willReturn($selectedAuthor);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([$defaultAuthor]);

        $controller = new TestArticleController();

        $response = $controller->authorFilter(new Request(['q' => '', 'author' => '12']), $userRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'options' => [
                [
                    'id' => 12,
                    'label' => 'Older Author <older-author@example.com>',
                ],
                [
                    'id' => 21,
                    'label' => 'Recent Author <recent-author@example.com>',
                ],
            ],
        ], json_decode((string) $response->getContent(), true));
    }

    public function testAuthorFilterTreatsMalformedQueryAsEmptySearch(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);

        $controller = new TestArticleController();

        $response = $controller->authorFilter(new Request(['q' => ['foo']]), $userRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['options' => []], json_decode((string) $response->getContent(), true));
    }

    public function testNewDisplaysPolishFlashMessageWhenAdminLanguageIsPolish(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Article::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $articlePublisher = $this->createMock(ArticlePublisher::class);
        $articlePublisher
            ->expects($this->once())
            ->method('prepareForSave')
            ->with($this->callback(static fn (Article $article): bool => 'Nowy artykul' === $article->getTitle()));

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $keywordRepository = $this->createMock(ArticleKeywordRepository::class);
        $keywordRepository
            ->expects($this->once())
            ->method('findForArticleAssignment')
            ->willReturn([]);

        $controller = new TestArticleController();
        $controller->authenticatedUser = (new User())
            ->setEmail('author@example.com')
            ->setPassword('hashed-password');
        $request = new Request([], [
            'article' => [
                'title' => 'Nowy artykul',
                'language' => 'pl',
                'excerpt' => 'Krotki opis',
                'headlineImageEnabled' => '1',
                'headlineImage' => '/assets/img/article.png',
                'content' => 'Tresc artykulu',
                'status' => 'draft',
                'category' => '',
                'keywords' => [],
                'publishedAt' => '',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->new(
            $request,
            $entityManager,
            $articlePublisher,
            $categoryRepository,
            $keywordRepository,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame([['success', 'Artykuł został dodany.']], $controller->flashes);
    }

    public function testEditDisplaysPolishFlashMessageWhenAdminLanguageIsPolish(): void
    {
        $article = (new Article())
            ->setTitle('Stary tytul')
            ->setSlug('stary-tytul')
            ->setContent('Stara tresc');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $articlePublisher = $this->createMock(ArticlePublisher::class);
        $articlePublisher
            ->expects($this->once())
            ->method('prepareForSave')
            ->with($article);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $keywordRepository = $this->createMock(ArticleKeywordRepository::class);
        $keywordRepository
            ->expects($this->once())
            ->method('findForArticleAssignment')
            ->willReturn([]);

        $controller = new TestArticleController();
        $controller->authenticatedUser = (new User())
            ->setEmail('editor@example.com')
            ->setPassword('hashed-password');
        $request = new Request([], [
            'article' => [
                'title' => 'Zmieniony tytul',
                'language' => 'pl',
                'excerpt' => 'Zmieniony opis',
                'headlineImageEnabled' => '1',
                'headlineImage' => '/assets/img/article-updated.png',
                'content' => 'Zmieniona tresc',
                'status' => 'draft',
                'category' => '',
                'keywords' => [],
                'publishedAt' => '',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->edit(
            $article,
            $request,
            $entityManager,
            $articlePublisher,
            $categoryRepository,
            $keywordRepository,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame('Zmieniony tytul', $article->getTitle());
        $this->assertSame([['success', 'Artykuł został zaktualizowany.']], $controller->flashes);
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
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

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
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

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

    public function testPublishKeepsExistingPublicationDateForPersistedLegacyData(): void
    {
        $publishedAt = new \DateTimeImmutable('2026-04-20 12:00:00', new \DateTimeZone('Europe/Warsaw'));
        $currentUser = (new User())
            ->setEmail('publisher@example.com')
            ->setPassword('hashed-password');
        // Covers persisted legacy/imported rows that already carry publication metadata.
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article')
            ->setStatus(ArticleStatus::DRAFT)
            ->setPublishedAt($publishedAt);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');
        $articlePublisher = new ArticlePublisher(
            $this->createMock(ArticleRepository::class),
            new ArticleSlugger(),
        );
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->publish($article, $request, $entityManager, $articlePublisher, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame(ArticleStatus::PUBLISHED, $article->getStatus());
        $this->assertSame($currentUser, $article->getUpdatedBy());
        $this->assertSame($publishedAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $article->getPublishedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $article->getPublishedAt()?->getTimezone()->getName());
        $this->assertSame([['success', 'Artykuł został opublikowany.']], $controller->flashes);
    }

    public function testPublishSetsPublicationDateWhenMissing(): void
    {
        $article = (new Article())
            ->setTitle('Test article')
            ->setSlug('test-article')
            ->setStatus(ArticleStatus::DRAFT)
            ->setPublishedAt(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');
        $articlePublisher = new ArticlePublisher(
            $this->createMock(ArticleRepository::class),
            new ArticleSlugger(),
        );
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $controller = new TestArticleController();
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->publish($article, $request, $entityManager, $articlePublisher, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(ArticleStatus::PUBLISHED, $article->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $article->getPublishedAt());
        $this->assertSame('UTC', $article->getPublishedAt()?->getTimezone()->getName());
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
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $controller = new TestArticleController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->export($article, $request, $queueRepository, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame([['success', 'Eksport artykułu został dodany do kolejki.']], $controller->flashes);
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
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $controller = new TestArticleController();
        $controller->csrfTokenIsValid = true;

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->export($article, $request, $queueRepository, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/articles', $response->getTargetUrl());
        $this->assertSame([['success', 'Eksport artykułu jest już w kolejce.']], $controller->flashes);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

    private function renderArticleIndexWithEmptyAuthorFilter(Request $request, bool $expectAuthorLookup): TestArticleController
    {
        $settings = (new BlogSettings())
            ->setAdminListingItemsPerPage(10);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countForAdminIndex')
            ->with(null, null, null)
            ->willReturn(0);
        $articleRepository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 10, null, null, null, 'desc')
            ->willReturn([]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->never())
            ->method('find');
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findForArticleAuthorFilter')
            ->with('', 10)
            ->willReturn([]);

        if ($expectAuthorLookup) {
            $userRepository
                ->expects($this->once())
                ->method('findArticleAuthorById')
                ->with(404)
                ->willReturn(null);
        } else {
            $userRepository
                ->expects($this->never())
                ->method('findArticleAuthorById');
        }

        $blogSettingsProvider = $this->createMock(BlogSettingsProvider::class);
        $blogSettingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestArticleController();

        $controller->index(
            $request,
            $articleRepository,
            $categoryRepository,
            $userRepository,
            $blogSettingsProvider,
            new PaginationBuilder(),
        );

        return $controller;
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

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
