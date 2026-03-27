<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Enum\ArticleStatus;
use App\Form\ArticleType;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleRepository;
use App\Service\ArticlePublisher;
use App\Service\BlogSettingsProvider;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/articles')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'admin_article_index', methods: ['GET'])]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
    ): Response
    {
        $settings = $blogSettingsProvider->getSettings();
        $articlesPerPage = max(1, $settings->getAdminArticlesPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalArticles = $articleRepository->count([]);
        $totalPages = max(1, (int) ceil($totalArticles / $articlesPerPage));
        $currentPage = min($requestedPage, $totalPages);

        return $this->render('admin/article/index.html.twig', [
            'articles' => $articleRepository->findPaginatedOrderedByCreatedDate($currentPage, $articlesPerPage),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $paginationBuilder->buildPaginationItems($currentPage, $totalPages),
        ]);
    }

    #[Route('/new', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticlePublisher $articlePublisher,
    ): Response {
        $article = new Article();
        $currentUser = $this->resolveAuthenticatedUser();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $articlePublisher->prepareForSave($article);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $currentUser) {
                $article
                    ->setCreatedBy($currentUser)
                    ->setUpdatedBy($currentUser);
            }

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
        $currentUser = $this->resolveAuthenticatedUser();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $articlePublisher->prepareForSave($article);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedBy($currentUser);
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
            ->setPublishedAt(null)
            ->setUpdatedBy($this->resolveAuthenticatedUser());

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
        $article->setUpdatedBy($this->resolveAuthenticatedUser());
        $articlePublisher->prepareForSave($article);

        $entityManager->flush();

        $this->addFlash('success', 'Article published.');

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/export', name: 'admin_article_export', methods: ['POST'])]
    public function export(
        Article $article,
        Request $request,
        ArticleExportQueueRepository $articleExportQueueRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('export_article_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->queueArticles([$article], $articleExportQueueRepository);

        if (0 === $result['queued']) {
            $this->addFlash('success', 'Article export is already queued.');

            return $this->redirectToRoute('admin_article_index');
        }

        $this->addFlash('success', 'Article export added to the queue.');

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/assign-to-me', name: 'admin_article_assign_to_me', methods: ['POST'])]
    public function assignToMe(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('assign_article_to_me_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $currentUser = $this->resolveAuthenticatedUser();
        if (null === $currentUser) {
            throw $this->createAccessDeniedException('User must be authenticated.');
        }

        if (null !== $article->getCreatedBy()) {
            $this->addFlash(
                'error',
                'pl' === $userLanguageResolver->getLanguage()
                    ? 'Artykuł ma już przypisanego autora.'
                    : 'Article already has an author assigned.'
            );

            return $this->redirectToRoute('admin_article_index');
        }

        $article
            ->setCreatedBy($currentUser)
            ->setUpdatedBy($currentUser);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'pl' === $userLanguageResolver->getLanguage()
                ? 'Autor artykułu został przypisany.'
                : 'Article author assigned.'
        );

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/export-selected', name: 'admin_article_export_selected', methods: ['POST'])]
    public function exportSelected(
        Request $request,
        ArticleRepository $articleRepository,
        ArticleExportQueueRepository $articleExportQueueRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('export_articles_bulk', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $articleIds = array_values(array_unique(array_filter(
            array_map('intval', $request->request->all('article_ids')),
            static fn (int $articleId): bool => $articleId > 0,
        )));

        if ([] === $articleIds) {
            $this->addFlash('error', 'Select at least one article to export.');

            return $this->redirectToRoute('admin_article_index');
        }

        $articles = $articleRepository->findBy(['id' => $articleIds]);
        $result = $this->queueArticles($articles, $articleExportQueueRepository);

        if (0 === $result['queued']) {
            $this->addFlash('success', 'Selected article exports are already queued.');

            return $this->redirectToRoute('admin_article_index');
        }

        if (0 === $result['skipped']) {
            $this->addFlash('success', sprintf('%d article export(s) added to the queue.', $result['queued']));

            return $this->redirectToRoute('admin_article_index');
        }

        $this->addFlash(
            'success',
            sprintf(
                '%d article export(s) added to the queue. %d already queued item(s) skipped.',
                $result['queued'],
                $result['skipped'],
            )
        );

        return $this->redirectToRoute('admin_article_index');
    }

    #[Route('/{id}/delete', name: 'admin_article_delete', methods: ['POST'])]
    public function delete(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleExportQueueRepository $articleExportQueueRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_'.$article->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (ArticleStatus::ARCHIVED !== $article->getStatus()) {
            $this->addFlash('error', 'Usunąć można tylko zarchiwizowany artykuł.');

            return $this->redirectToRoute('admin_article_index');
        }

        foreach ($articleExportQueueRepository->findPendingForArticle($article) as $queueItem) {
            $entityManager->remove($queueItem);
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article deleted.');

        return $this->redirectToRoute('admin_article_index');
    }

    /**
     * @param list<Article> $articles
     *
     * @return array{queued: int, skipped: int}
     */
    private function queueArticles(
        array $articles,
        ArticleExportQueueRepository $articleExportQueueRepository,
    ): array {
        $queued = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            if (!$articleExportQueueRepository->enqueuePending($article)) {
                ++$skipped;

                continue;
            }
            ++$queued;
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }

    private function resolveAuthenticatedUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
