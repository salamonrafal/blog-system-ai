<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleCategory;
use App\Entity\User;
use App\Form\ArticleCategoryType;
use App\Repository\ArticleCategoryRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Service\CategorySlugger;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
class ArticleCategoryController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    #[Route('', name: 'admin_article_category_index', methods: ['GET'])]
    public function index(ArticleCategoryRepository $articleCategoryRepository): Response
    {
        return $this->render('admin/article_category/index.html.twig', [
            'categories' => $articleCategoryRepository->findForAdminIndex(),
            'category_stats' => [
                'all' => $articleCategoryRepository->count([]),
                'active' => $articleCategoryRepository->countActive(),
                'inactive' => $articleCategoryRepository->countInactive(),
            ],
        ]);
    }

    #[Route('/new', name: 'admin_article_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserLanguageResolver $userLanguageResolver, CategorySlugger $categorySlugger): Response
    {
        $category = new ArticleCategory();
        $form = $this->createForm(ArticleCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->syncTranslations($category, $form);
            $this->refreshSlugIfMissing($category, $categorySlugger);
            $this->addSlugErrorIfStillMissing($form, $category, $userLanguageResolver);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Kategoria została dodana.', 'Category created.'));

            return $this->redirectToRoute('admin_article_category_index');
        }

        return $this->render('admin/article_category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_article_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        ArticleCategory $category,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
        CategorySlugger $categorySlugger,
    ): Response {
        $form = $this->createForm(ArticleCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->syncTranslations($category, $form);
            $this->refreshSlugIfMissing($category, $categorySlugger);
            $this->addSlugErrorIfStillMissing($form, $category, $userLanguageResolver);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Kategoria została zaktualizowana.', 'Category updated.'));

            return $this->redirectToRoute('admin_article_category_index');
        }

        return $this->render('admin/article_category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_article_category_delete', methods: ['POST'])]
    public function delete(
        ArticleCategory $category,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($category);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Kategoria została usunięta.', 'Category deleted.'));

        return $this->redirectToRoute('admin_article_category_index');
    }

    #[Route('/{id}/export', name: 'admin_article_category_export', methods: ['POST'])]
    public function export(
        ArticleCategory $category,
        Request $request,
        CategoryExportQueueRepository $categoryExportQueueRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('export_article_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->queueCategories([$category], $categoryExportQueueRepository, $this->resolveAuthenticatedUser());
        if (0 === $result['queued']) {
            $this->addFlash('success', $userLanguageResolver->translate('Eksport kategorii jest już w kolejce.', 'Category export is already queued.'));

            return $this->redirectToRoute('admin_article_category_index');
        }

        $this->addFlash('success', $userLanguageResolver->translate('Eksport kategorii został dodany do kolejki.', 'Category export added to the queue.'));

        return $this->redirectToRoute('admin_article_category_index');
    }

    #[Route('/export-selected', name: 'admin_article_category_export_selected', methods: ['POST'])]
    public function exportSelected(
        Request $request,
        ArticleCategoryRepository $articleCategoryRepository,
        CategoryExportQueueRepository $categoryExportQueueRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('export_article_categories_bulk', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $categoryIds = array_values(array_unique(array_filter(
            array_map('intval', $request->request->all('category_ids')),
            static fn (int $categoryId): bool => $categoryId > 0,
        )));

        if ([] === $categoryIds) {
            $this->addFlash('error', $userLanguageResolver->translate('Wybierz co najmniej jedną kategorię do eksportu.', 'Select at least one category to export.'));

            return $this->redirectToRoute('admin_article_category_index');
        }

        $categories = $articleCategoryRepository->findBy(['id' => $categoryIds]);
        $result = $this->queueCategories($categories, $categoryExportQueueRepository, $this->resolveAuthenticatedUser());

        if (0 === $result['queued']) {
            $this->addFlash('success', $userLanguageResolver->translate('Eksport zaznaczonych kategorii jest już w kolejce.', 'Selected category exports are already queued.'));

            return $this->redirectToRoute('admin_article_category_index');
        }

        if (0 === $result['skipped']) {
            $this->addFlash(
                'success',
                $userLanguageResolver->translate('flash_category_exports_queued_count', [
                    '%queued%' => $result['queued'],
                ])
            );

            return $this->redirectToRoute('admin_article_category_index');
        }

        $this->addFlash(
            'success',
            $userLanguageResolver->translate('flash_category_exports_queued_with_skipped', [
                '%queued%' => $result['queued'],
                '%skipped%' => $result['skipped'],
            ])
        );

        return $this->redirectToRoute('admin_article_category_index');
    }

    private function syncTranslations(ArticleCategory $category, FormInterface $form): void
    {
        /** @var array<string, mixed> $titles */
        $titles = $form->get('titles')->getData();
        /** @var array<string, mixed> $descriptions */
        $descriptions = $form->get('descriptions')->getData();

        $category
            ->setTitles($titles)
            ->setDescriptions($descriptions);
    }

    private function refreshSlugIfMissing(ArticleCategory $category, CategorySlugger $categorySlugger): void
    {
        if ('' !== trim($category->getSlug())) {
            return;
        }

        $baseValue = $category->getTitle('pl', null) ?? $category->getName();
        if ('' === trim((string) $baseValue)) {
            return;
        }

        $categorySlugger->refreshSlug($category);
    }

    private function addSlugErrorIfStillMissing(FormInterface $form, ArticleCategory $category, UserLanguageResolver $userLanguageResolver): void
    {
        if ('' !== trim($category->getSlug())) {
            return;
        }

        $form->addError(new FormError($userLanguageResolver->translate(
            'Nie udało się wygenerować sluga kategorii.',
            'Failed to generate a category slug.'
        )));
    }

    /**
     * @param list<ArticleCategory> $categories
     *
     * @return array{queued: int, skipped: int}
     */
    private function queueCategories(
        array $categories,
        CategoryExportQueueRepository $categoryExportQueueRepository,
        ?User $requestedBy,
    ): array {
        $queued = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            if (!$categoryExportQueueRepository->enqueuePending($category, $requestedBy)) {
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

}
