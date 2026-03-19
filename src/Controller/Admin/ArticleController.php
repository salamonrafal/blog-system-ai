<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\ArticlePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/articles')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'admin_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $articleRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticlePublisher $articlePublisher,
    ): Response {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $articlePublisher->prepareForSave($article);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article created.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_article_edit', methods: ['GET', 'POST'])]
    public function edit(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticlePublisher $articlePublisher,
    ): Response {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $articlePublisher->prepareForSave($article);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Article updated.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/archive', name: 'admin_article_archive', methods: ['POST'])]
    public function archive(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('archive_article_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $article
            ->setStatus(ArticleStatus::ARCHIVED)
            ->setPublishedAt(null);

        $entityManager->flush();

        $this->addFlash('success', 'Article archived.');

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/publish', name: 'admin_article_publish', methods: ['POST'])]
    public function publish(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticlePublisher $articlePublisher,
    ): Response {
        if (!$this->isCsrfTokenValid('publish_article_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (ArticleStatus::PUBLISHED === $article->getStatus()) {
            $this->addFlash('error', 'Article is already published.');

            return $this->redirectToRoute('admin_article_index');
        }

        $article->setStatus(ArticleStatus::PUBLISHED);
        $articlePublisher->prepareForSave($article);

        $entityManager->flush();

        $this->addFlash('success', 'Article published.');

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/delete', name: 'admin_article_delete', methods: ['POST'])]
    public function delete(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (ArticleStatus::ARCHIVED !== $article->getStatus()) {
            $this->addFlash('error', 'Usunac mozna tylko zarchiwizowany artykul.');

            return $this->redirectToRoute('admin_article_index');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article deleted.');

        return $this->redirectToRoute('admin_article_index');
    }
}
