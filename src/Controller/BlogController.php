<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
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
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
    ): Response
    {
        $language = ArticleLanguage::tryFrom((string) $request->query->get('lang', ''));
        $settings = $blogSettingsProvider->getSettings();
        $articlesPerPage = max(1, $settings->getArticlesPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalArticles = $articleRepository->countPublished($language);
        $totalPages = max(1, (int) ceil($totalArticles / $articlesPerPage));
        $currentPage = min($requestedPage, $totalPages);

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepository->findPublishedPaginated($language, $currentPage, $articlesPerPage),
            'current_language' => $language,
            'language_options' => ArticleLanguage::cases(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $paginationBuilder->buildPaginationItems($currentPage, $totalPages),
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
}
