<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArticleCategory;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Service\ArticleSlugger;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/', name: 'blog_index', methods: ['GET'])]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        ArticleSlugger $articleSlugger,
    ): Response
    {
        return $this->renderIndex(
            $request,
            $articleRepository,
            $articleCategoryRepository,
            $blogSettingsProvider,
            $paginationBuilder,
            $articleSlugger,
        );
    }

    #[Route('/category/{slug}', name: 'blog_category', methods: ['GET'])]
    public function category(
        string $slug,
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        ArticleSlugger $articleSlugger,
    ): Response
    {
        return $this->renderIndex(
            $request,
            $articleRepository,
            $articleCategoryRepository,
            $blogSettingsProvider,
            $paginationBuilder,
            $articleSlugger,
            $slug,
        );
    }

    #[Route('/articles/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(string $slug, ArticleRepository $articleRepository, ArticleSlugger $articleSlugger): Response
    {
        $article = $articleRepository->findOneBySlug($slug);

        if (null === $article) {
            throw $this->createNotFoundException('Article not found.');
        }

        if (ArticleStatus::PUBLISHED !== $article->getStatus()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'article_category_slug' => $article->getCategory() ? $articleSlugger->slugify($article->getCategory()->getName()) : null,
        ]);
    }

    private function renderIndex(
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        ArticleSlugger $articleSlugger,
        ?string $categorySlug = null,
    ): Response
    {
        $language = ArticleLanguage::tryFrom((string) $request->query->get('lang', ''));
        $settings = $blogSettingsProvider->getSettings();
        $categoryLinks = $this->buildCategoryLinks(
            $articleCategoryRepository->findActiveOrderedByName(),
            $articleSlugger,
        );
        $currentCategory = $this->resolveCurrentCategory($categoryLinks, $categorySlug);

        if (null !== $categorySlug && null === $currentCategory) {
            throw $this->createNotFoundException('Category not found.');
        }

        $articlesPerPage = max(1, $settings->getArticlesPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalArticles = $articleRepository->countPublished($language, $currentCategory);
        $totalPages = max(1, (int) ceil($totalArticles / $articlesPerPage));
        $currentPage = min($requestedPage, $totalPages);
        $currentCategorySlug = $this->findCategorySlug($categoryLinks, $currentCategory);

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepository->findPublishedPaginated($language, $currentPage, $articlesPerPage, $currentCategory),
            'categories' => $categoryLinks,
            'current_category' => $currentCategory,
            'current_category_slug' => $currentCategorySlug,
            'current_language' => $language,
            'language_options' => ArticleLanguage::cases(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $paginationBuilder->buildPaginationItems($currentPage, $totalPages),
            'pagination_route' => null === $currentCategory ? 'blog_index' : 'blog_category',
            'pagination_route_params' => array_filter([
                'slug' => $currentCategorySlug,
                'lang' => $language?->value,
            ], static fn (mixed $value): bool => null !== $value && '' !== $value),
        ]);
    }

    /**
     * @param list<ArticleCategory> $categories
     * @return list<array{category: ArticleCategory, slug: string}>
     */
    private function buildCategoryLinks(array $categories, ArticleSlugger $articleSlugger): array
    {
        return array_map(
            static fn (ArticleCategory $category): array => [
                'category' => $category,
                'slug' => $articleSlugger->slugify($category->getName()),
            ],
            $categories,
        );
    }

    /**
     * @param list<array{category: ArticleCategory, slug: string}> $categoryLinks
     */
    private function resolveCurrentCategory(array $categoryLinks, ?string $categorySlug): ?ArticleCategory
    {
        if (null === $categorySlug || '' === $categorySlug) {
            return null;
        }

        foreach ($categoryLinks as $categoryLink) {
            if ($categoryLink['slug'] === $categorySlug) {
                return $categoryLink['category'];
            }
        }

        return null;
    }

    /**
     * @param list<array{category: ArticleCategory, slug: string}> $categoryLinks
     */
    private function findCategorySlug(array $categoryLinks, ?ArticleCategory $category): ?string
    {
        if (null === $category) {
            return null;
        }

        foreach ($categoryLinks as $categoryLink) {
            if ($categoryLink['category'] === $category) {
                return $categoryLink['slug'];
            }
        }

        return null;
    }
}
