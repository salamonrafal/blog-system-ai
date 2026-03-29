<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleCategory;
use App\Form\ArticleCategoryType;
use App\Repository\ArticleCategoryRepository;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
class ArticleCategoryController extends AbstractController
{
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
    public function new(Request $request, EntityManagerInterface $entityManager, UserLanguageResolver $userLanguageResolver): Response
    {
        $category = new ArticleCategory();
        $form = $this->createForm(ArticleCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTranslations($category, $form);
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
    ): Response {
        $form = $this->createForm(ArticleCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTranslations($category, $form);
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

}
