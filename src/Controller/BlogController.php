<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $language = ArticleLanguage::tryFrom((string) $request->query->get('lang', ''));

        return $this->render('blog/index.html.twig', [
            'articles' => $articleRepository->findPublishedOrderedByDate($language),
            'current_language' => $language,
            'language_options' => ArticleLanguage::cases(),
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
