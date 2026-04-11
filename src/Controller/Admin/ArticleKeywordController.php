<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleKeyword;
use App\Form\ArticleKeywordType;
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Repository\ArticleKeywordRepository;
use App\Service\ArticleKeywordNameGenerator;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/article-keywords')]
class ArticleKeywordController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    #[Route('', name: 'admin_article_keyword_index', methods: ['GET'])]
    public function index(ArticleKeywordRepository $articleKeywordRepository): Response
    {
        return $this->render('admin/article_keyword/index.html.twig', [
            'keywords' => $articleKeywordRepository->findForAdminIndex(),
            'keyword_stats' => [
                'all' => $articleKeywordRepository->count([]),
                'active' => $articleKeywordRepository->countActive(),
                'inactive' => $articleKeywordRepository->countInactive(),
            ],
        ]);
    }

    #[Route('/new', name: 'admin_article_keyword_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleKeywordNameGenerator $articleKeywordNameGenerator,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $keyword = new ArticleKeyword();
        $form = $this->createForm(ArticleKeywordType::class, $keyword);
        $form->handleRequest($request);

        if ($form->isSubmitted() && '' !== trim($keyword->getName())) {
            $articleKeywordNameGenerator->refreshName($keyword);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($keyword);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate(
                'Słowo kluczowe zostało dodane.',
                'Keyword created.',
            ));

            return $this->redirectToRoute('admin_article_keyword_index');
        }

        return $this->render('admin/article_keyword/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_article_keyword_edit', methods: ['GET', 'POST'])]
    public function edit(
        ArticleKeyword $keyword,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleKeywordNameGenerator $articleKeywordNameGenerator,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(ArticleKeywordType::class, $keyword);
        $form->handleRequest($request);

        if ($form->isSubmitted() && '' !== trim($keyword->getName())) {
            $articleKeywordNameGenerator->refreshName($keyword);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate(
                'Słowo kluczowe zostało zaktualizowane.',
                'Keyword updated.',
            ));

            return $this->redirectToRoute('admin_article_keyword_index');
        }

        return $this->render('admin/article_keyword/edit.html.twig', [
            'keyword' => $keyword,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_article_keyword_delete', methods: ['POST'])]
    public function delete(
        ArticleKeyword $keyword,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_keyword_'.$keyword->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($keyword);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate(
            'Słowo kluczowe zostało usunięte.',
            'Keyword deleted.',
        ));

        return $this->redirectToRoute('admin_article_keyword_index');
    }

    #[Route('/export', name: 'admin_article_keyword_export', methods: ['POST'])]
    public function export(
        Request $request,
        ArticleKeywordExportQueueRepository $articleKeywordExportQueueRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('export_article_keywords', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$articleKeywordExportQueueRepository->enqueuePending($this->resolveAuthenticatedUser())) {
            $this->addFlash('success', $userLanguageResolver->translate(
                'Eksport słów kluczowych jest już w kolejce.',
                'Keyword export is already queued.',
            ));

            return $this->redirectToRoute('admin_article_keyword_index');
        }

        $this->addFlash('success', $userLanguageResolver->translate(
            'Eksport słów kluczowych został dodany do kolejki.',
            'Keyword export added to the queue.',
        ));

        return $this->redirectToRoute('admin_article_keyword_index');
    }
}
