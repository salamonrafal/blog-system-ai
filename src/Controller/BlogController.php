<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArticleCategory;
use App\Entity\ArticleKeyword;
use App\Entity\Article;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleKeywordLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleKeywordRepository;
use App\Repository\ArticleRepository;
use App\Service\ArticleMarkupRenderer;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
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
        ArticleKeywordRepository $articleKeywordRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        UserLanguageResolver $userLanguageResolver,
    ): Response
    {
        return $this->renderIndex(
            $request,
            $articleRepository,
            $articleCategoryRepository,
            $articleKeywordRepository,
            $blogSettingsProvider,
            $paginationBuilder,
            $userLanguageResolver,
        );
    }

    #[Route('/category/{slug}', name: 'blog_category', methods: ['GET'])]
    public function category(
        string $slug,
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleKeywordRepository $articleKeywordRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        UserLanguageResolver $userLanguageResolver,
    ): Response
    {
        return $this->renderIndex(
            $request,
            $articleRepository,
            $articleCategoryRepository,
            $articleKeywordRepository,
            $blogSettingsProvider,
            $paginationBuilder,
            $userLanguageResolver,
            $slug,
        );
    }

    #[Route('/keyword/{language}/{name}', name: 'blog_keyword', methods: ['GET'])]
    public function keyword(
        string $language,
        string $name,
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleKeywordRepository $articleKeywordRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        UserLanguageResolver $userLanguageResolver,
    ): Response
    {
        return $this->renderIndex(
            $request,
            $articleRepository,
            $articleCategoryRepository,
            $articleKeywordRepository,
            $blogSettingsProvider,
            $paginationBuilder,
            $userLanguageResolver,
            null,
            $this->resolveCurrentKeyword($articleKeywordRepository, $language, $name),
        );
    }

    #[Route('/articles/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(
        string $slug,
        ArticleRepository $articleRepository,
        UserLanguageResolver $userLanguageResolver,
        ArticleMarkupRenderer $articleMarkupRenderer,
    ): Response
    {
        $article = $articleRepository->findOneBySlug($slug);

        if (null === $article) {
            throw $this->createNotFoundException('Article not found.');
        }

        if (ArticleStatus::PUBLISHED !== $article->getStatus()) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        $userLanguage = null;
        $articleCategory = $article->getCategory();
        $articleCategoryRouteParams = null;
        if (null !== $articleCategory && $articleCategory->isActive()) {
            $userLanguage = $userLanguageResolver->getLanguage();
            $articleCategoryRouteParams = [
                'slug' => $articleCategory->getSlug(),
                'lang' => $userLanguage,
            ];
        }

        $articleKeywordLinks = [];
        if (!$article->getKeywords()->isEmpty()) {
            $articleKeywordLinks = $this->buildArticleKeywordLinks($article);
        }

        $recommendedArticles = $articleRepository->findRecommendedPublished($article);
        $tableOfContents = $article->isTableOfContentsEnabled()
            ? $articleMarkupRenderer->extractTableOfContents($article->getContent())
            : [];

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'article_category_route_params' => $articleCategoryRouteParams,
            'article_keywords' => $articleKeywordLinks,
            'recommended_articles' => $recommendedArticles,
            'table_of_contents' => $tableOfContents,
        ]);
    }

    private function renderIndex(
        Request $request,
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleKeywordRepository $articleKeywordRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
        UserLanguageResolver $userLanguageResolver,
        ?string $categorySlug = null,
        ?ArticleKeyword $currentKeyword = null,
    ): Response
    {
        $language = ArticleLanguage::tryFrom((string) $request->query->get('lang', ''))
            ?? ArticleLanguage::tryFrom($userLanguageResolver->getLanguage());
        $settings = $blogSettingsProvider->getSettings();
        $categoryLinks = $this->buildCategoryLinks($articleCategoryRepository->findActiveOrderedByName());
        $currentCategory = $this->resolveCurrentCategory($categoryLinks, $categorySlug);

        if (null !== $categorySlug && null === $currentCategory) {
            throw $this->createNotFoundException('Category not found.');
        }

        $articlesPerPage = max(1, $settings->getArticlesPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalArticles = $articleRepository->countPublished(null, $currentCategory, $currentKeyword);
        $totalPages = max(1, (int) ceil($totalArticles / $articlesPerPage));
        $currentPage = min($requestedPage, $totalPages);
        $currentCategorySlug = $this->findCategorySlug($categoryLinks, $currentCategory);
        $paginationRoute = $this->resolvePaginationRoute($currentCategory, $currentKeyword);
        $paginationRouteParams = $this->buildPaginationRouteParams($currentCategorySlug, $currentKeyword, $language);
        $topKeywordLinks = null;

        if (null === $currentCategory && null === $currentKeyword) {
            $topKeywordLinks = $this->buildTopKeywordLinks($articleKeywordRepository->findTopUsedInPublishedArticles(5));
        }

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepository->findPublishedPaginated(null, $currentPage, $articlesPerPage, $currentCategory, $currentKeyword),
            'categories' => $categoryLinks,
            'current_category' => $currentCategory,
            'current_category_slug' => $currentCategorySlug,
            'current_keyword' => $currentKeyword,
            'current_language' => $language,
            'language_options' => ArticleLanguage::cases(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $paginationBuilder->buildPaginationItems($currentPage, $totalPages),
            'pagination_route' => $paginationRoute,
            'pagination_route_params' => $paginationRouteParams,
            'top_keywords' => $topKeywordLinks,
        ]);
    }

    /**
     * @param list<ArticleCategory> $categories
     * @return list<array{category: ArticleCategory, slug: string}>
     */
    private function buildCategoryLinks(array $categories): array
    {
        return array_map(
            static fn (ArticleCategory $category): array => [
                'category' => $category,
                'slug' => $category->getSlug(),
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

    private function resolveCurrentKeyword(
        ArticleKeywordRepository $articleKeywordRepository,
        string $language,
        string $name,
    ): ArticleKeyword
    {
        $keywordLanguage = ArticleKeywordLanguage::tryFrom(strtolower(trim($language)));
        if (null === $keywordLanguage) {
            throw $this->createNotFoundException('Keyword not found.');
        }

        $keyword = $articleKeywordRepository->findOneActiveByLanguageAndName($keywordLanguage, $name);
        if (null === $keyword) {
            throw $this->createNotFoundException('Keyword not found.');
        }

        return $keyword;
    }

    /**
     * @return list<array{keyword: ArticleKeyword, route_params: array{language: string, name: string}}>
     */
    private function buildArticleKeywordLinks(Article $article): array
    {
        $keywords = array_filter(
            $article->getKeywords()->toArray(),
            static fn (mixed $keyword): bool => $keyword instanceof ArticleKeyword
                && $keyword->isActive()
                && $keyword->getLanguage()->matchesArticleLanguage($article->getLanguage()),
        );

        usort(
            $keywords,
            static fn (ArticleKeyword $left, ArticleKeyword $right): int => [$left->getName(), $left->getId() ?? 0]
                <=> [$right->getName(), $right->getId() ?? 0],
        );

        return array_map(
            static fn (ArticleKeyword $keyword): array => [
                'keyword' => $keyword,
                'route_params' => [
                    'language' => $keyword->getLanguage()->value,
                    'name' => $keyword->getName(),
                ],
            ],
            $keywords,
        );
    }

    /**
     * @param list<array{keyword: ArticleKeyword, article_count: int}> $topKeywords
     * @return list<array{keyword: ArticleKeyword, article_count: int, route_params: array{language: string, name: string}}>
     */
    private function buildTopKeywordLinks(array $topKeywords): array
    {
        return array_map(
            static fn (array $item): array => [
                'keyword' => $item['keyword'],
                'article_count' => $item['article_count'],
                'route_params' => [
                    'language' => $item['keyword']->getLanguage()->value,
                    'name' => $item['keyword']->getName(),
                ],
            ],
            $topKeywords,
        );
    }

    private function resolvePaginationRoute(?ArticleCategory $currentCategory, ?ArticleKeyword $currentKeyword): string
    {
        if (null !== $currentKeyword) {
            return 'blog_keyword';
        }

        return null === $currentCategory ? 'blog_index' : 'blog_category';
    }

    /**
     * @return array{slug?: string, language?: string, name?: string, lang?: string}
     */
    private function buildPaginationRouteParams(
        ?string $currentCategorySlug,
        ?ArticleKeyword $currentKeyword,
        ?ArticleLanguage $language,
    ): array {
        return array_filter([
            'slug' => $currentCategorySlug,
            'language' => $currentKeyword?->getLanguage()->value,
            'name' => $currentKeyword?->getName(),
            'lang' => null === $currentKeyword ? $language?->value : null,
        ], static fn (mixed $value): bool => null !== $value && '' !== $value);
    }
}
