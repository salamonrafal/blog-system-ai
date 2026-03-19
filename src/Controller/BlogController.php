<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use App\Service\BlogSettingsProvider;
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
        BlogSettingsProvider $blogSettingsProvider
    ): Response
    {
        $language = ArticleLanguage::tryFrom((string) $request->query->get('lang', ''));
        $settings = $blogSettingsProvider->getSettings();
        $articlesPerPage = max(1, $settings->getArticlesPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalArticles = $articleRepository->countPublished($language);
        $totalPages = (int) ceil($totalArticles / $articlesPerPage);
        $currentPage = min($requestedPage, max(1, $totalPages));

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepository->findPublishedPaginated($language, $currentPage, $articlesPerPage),
            'current_language' => $language,
            'language_options' => ArticleLanguage::cases(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $this->buildPaginationItems($currentPage, $totalPages),
        ]);
    }

    #[Route('/articles/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(string $slug, ArticleRepository $articleRepository): Response
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
        ]);
    }

    /**
     * @return list<int|string>
     */
    private function buildPaginationItems(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }

        $pages = [1, $totalPages];

        for ($page = $currentPage - 1; $page <= $currentPage + 1; ++$page) {
            if ($page > 1 && $page < $totalPages) {
                $pages[] = $page;
            }
        }

        sort($pages);

        $items = [];
        $previousPage = null;

        foreach (array_values(array_unique($pages)) as $page) {
            if (null !== $previousPage && $page - $previousPage > 1) {
                $items[] = '...';
            }

            $items[] = $page;
            $previousPage = $page;
        }

        return $items;
    }
}
