<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\BlogController;
use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\BlogSettings;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BlogControllerTest extends TestCase
{
    public function testIndexRendersArticlesAndCategoriesWithoutActiveFilter(): void
    {
        $request = new Request(['lang' => 'pl', 'page' => '2']);
        $settings = (new BlogSettings())->setArticlesPerPage(5);
        $article = (new Article())->setTitle('Article');
        $category = (new ArticleCategory())
            ->setName('Programowanie PHP')
            ->setSlug('programowanie-php')
            ->setTitle('pl', 'PHP');

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countPublished')
            ->with(ArticleLanguage::PL, null)
            ->willReturn(8);
        $articleRepository
            ->expects($this->once())
            ->method('findPublishedPaginated')
            ->with(ArticleLanguage::PL, 2, 5, null)
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([$category]);

        $settingsProvider = $this->createMock(BlogSettingsProvider::class);
        $settingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestBlogController();
        $response = $controller->index(
            $request,
            $articleRepository,
            $categoryRepository,
            $settingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('blog/index.html.twig', $controller->capturedView);
        $this->assertSame([$article], $controller->capturedParameters['articles']);
        $this->assertNull($controller->capturedParameters['current_category']);
        $this->assertSame('blog_index', $controller->capturedParameters['pagination_route']);
        $this->assertSame(['lang' => 'pl'], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame('programowanie-php', $controller->capturedParameters['categories'][0]['slug']);
        $this->assertSame([1, 2], $controller->capturedParameters['pagination_items']);
        $this->assertSame('PHP', $controller->capturedParameters['categories'][0]['category']->getLocalizedTitle('pl'));
    }

    public function testCategoryRendersOnlyArticlesFromMatchedCategory(): void
    {
        $request = new Request(['lang' => 'en']);
        $settings = (new BlogSettings())->setArticlesPerPage(6);
        $article = (new Article())->setTitle('Article in category');
        $category = (new ArticleCategory())
            ->setName('Sztuczna Inteligencja')
            ->setSlug('sztuczna-inteligencja')
            ->setTitle('en', 'Artificial Intelligence')
            ->setDescription('en', 'Articles about AI and machine learning.');

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('countPublished')
            ->with(ArticleLanguage::EN, $category)
            ->willReturn(1);
        $articleRepository
            ->expects($this->once())
            ->method('findPublishedPaginated')
            ->with(ArticleLanguage::EN, 1, 6, $category)
            ->willReturn([$article]);

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([$category]);

        $settingsProvider = $this->createMock(BlogSettingsProvider::class);
        $settingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $controller = new TestBlogController();
        $controller->category(
            'sztuczna-inteligencja',
            $request,
            $articleRepository,
            $categoryRepository,
            $settingsProvider,
            new PaginationBuilder(),
        );

        $this->assertSame($category, $controller->capturedParameters['current_category']);
        $this->assertSame('sztuczna-inteligencja', $controller->capturedParameters['current_category_slug']);
        $this->assertSame('blog_category', $controller->capturedParameters['pagination_route']);
        $this->assertSame([
            'slug' => 'sztuczna-inteligencja',
            'lang' => 'en',
        ], $controller->capturedParameters['pagination_route_params']);
        $this->assertSame('Artificial Intelligence', $controller->capturedParameters['current_category']->getLocalizedTitle('en'));
        $this->assertSame('Articles about AI and machine learning.', $controller->capturedParameters['current_category']->getLocalizedDescription('en'));
    }

    public function testCategoryThrowsNotFoundWhenSlugDoesNotMatchActiveCategory(): void
    {
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->never())
            ->method('countPublished');
        $articleRepository
            ->expects($this->never())
            ->method('findPublishedPaginated');

        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findActiveOrderedByName')
            ->willReturn([]);

        $settingsProvider = $this->createMock(BlogSettingsProvider::class);
        $settingsProvider
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn(new BlogSettings());

        $controller = new TestBlogController();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Category not found.');

        $controller->category(
            'missing-category',
            new Request(),
            $articleRepository,
            $categoryRepository,
            $settingsProvider,
            new PaginationBuilder(),
        );
    }

    public function testShowExposesCategorySlugForLinkedCategoryBadge(): void
    {
        $category = (new ArticleCategory())->setName('Architektura Systemow')->setSlug('architektura-systemow');
        $article = (new Article())
            ->setTitle('Article')
            ->setSlug('article')
            ->setStatus(ArticleStatus::PUBLISHED)
            ->setCategory($category);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->with('article')
            ->willReturn($article);
        $articleRepository
            ->expects($this->once())
            ->method('findRecommendedPublished')
            ->with($article)
            ->willReturn([]);

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('en');

        $controller = new TestBlogController();
        $response = $controller->show('article', $articleRepository, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('blog/show.html.twig', $controller->capturedView);
        $this->assertSame($article, $controller->capturedParameters['article']);
        $this->assertSame([
            'slug' => 'architektura-systemow',
            'lang' => 'en',
        ], $controller->capturedParameters['article_category_route_params']);
        $this->assertSame([], $controller->capturedParameters['recommended_articles']);
    }

    public function testShowDoesNotExposeCategoryRouteParamsForInactiveCategory(): void
    {
        $category = (new ArticleCategory())
            ->setName('Architektura Systemow')
            ->setSlug('architektura-systemow')
            ->setStatus(\App\Enum\ArticleCategoryStatus::INACTIVE);
        $article = (new Article())
            ->setTitle('Article')
            ->setSlug('article')
            ->setStatus(ArticleStatus::PUBLISHED)
            ->setCategory($category);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->with('article')
            ->willReturn($article);
        $articleRepository
            ->expects($this->once())
            ->method('findRecommendedPublished')
            ->with($article)
            ->willReturn([]);

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->never())
            ->method('getLanguage');

        $controller = new TestBlogController();
        $response = $controller->show('article', $articleRepository, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($controller->capturedParameters['article_category_route_params']);
        $this->assertSame([], $controller->capturedParameters['recommended_articles']);
    }

    public function testShowExposesRecommendedArticles(): void
    {
        $article = (new Article())
            ->setTitle('Article')
            ->setSlug('article')
            ->setStatus(ArticleStatus::PUBLISHED);
        $recommendedArticle = (new Article())
            ->setTitle('Recommended')
            ->setSlug('recommended')
            ->setStatus(ArticleStatus::PUBLISHED);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->with('article')
            ->willReturn($article);
        $articleRepository
            ->expects($this->once())
            ->method('findRecommendedPublished')
            ->with($article)
            ->willReturn([$recommendedArticle]);

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->never())
            ->method('getLanguage');

        $controller = new TestBlogController();
        $controller->show('article', $articleRepository, $userLanguageResolver);

        $this->assertSame([$recommendedArticle], $controller->capturedParameters['recommended_articles']);
    }
}

final class TestBlogController extends BlogController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }
}
