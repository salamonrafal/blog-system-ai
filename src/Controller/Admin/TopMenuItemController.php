<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\TopMenuItem;
use App\Form\TopMenuItemType;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\TopMenuCacheManager;
use App\Service\TopMenuItemUniqueNameGenerator;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/top-menu')]
class TopMenuItemController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    #[Route('', name: 'admin_top_menu_index', methods: ['GET'])]
    public function index(TopMenuItemRepository $topMenuItemRepository): Response
    {
        return $this->render('admin/top_menu/index.html.twig', [
            'menu_items' => $topMenuItemRepository->findForAdminIndex(),
            'menu_stats' => [
                'all' => $topMenuItemRepository->count([]),
                'active' => $topMenuItemRepository->countActive(),
                'inactive' => $topMenuItemRepository->countInactive(),
            ],
        ]);
    }

    #[Route('/tree', name: 'admin_top_menu_tree', methods: ['GET'])]
    public function tree(TopMenuItemRepository $topMenuItemRepository): Response
    {
        return $this->render('admin/top_menu/tree.html.twig', [
            'menu_tree' => $topMenuItemRepository->findTreeForAdmin(),
        ]);
    }

    #[Route('/new', name: 'admin_top_menu_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TopMenuItemRepository $topMenuItemRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleRepository $articleRepository,
        UserLanguageResolver $userLanguageResolver,
        TopMenuItemUniqueNameGenerator $topMenuItemUniqueNameGenerator,
        TopMenuCacheManager $topMenuCacheManager,
    ): Response {
        $menuItem = new TopMenuItem();
        $requestedAfterId = $request->query->getInt('after');
        if ($requestedAfterId > 0) {
            $requestedAfterItem = $topMenuItemRepository->find($requestedAfterId);
            if ($requestedAfterItem instanceof TopMenuItem) {
                $menuItem
                    ->setParent($requestedAfterItem->getParent())
                    ->setPosition($requestedAfterItem->getPosition() + 1);
            }
        }

        $requestedParentId = $request->query->getInt('parent');
        if (null === $menuItem->getParent() && $requestedParentId > 0) {
            $requestedParent = $topMenuItemRepository->find($requestedParentId);
            if ($requestedParent instanceof TopMenuItem && null === $requestedParent->getParent()) {
                $menuItem->setParent($requestedParent);
            }
        }

        $form = $this->createEditorForm($menuItem, $topMenuItemRepository, $articleCategoryRepository, $articleRepository, $userLanguageResolver);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->syncTranslations($menuItem, $form);
            $menuItem->normalizeTargetConfiguration();
            $this->refreshUniqueNameIfMissing($menuItem, $topMenuItemUniqueNameGenerator);
            $this->addUniqueNameErrorIfStillMissing($form, $menuItem, $userLanguageResolver);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $topMenuItemRepository->applySiblingPositioning($menuItem);
            $entityManager->persist($menuItem);
            $entityManager->flush();
            $topMenuCacheManager->refresh();

            $this->addFlash('success', $userLanguageResolver->translate('Element menu został dodany.', 'Menu item created.'));

            return $this->redirectToRoute('admin_top_menu_index');
        }

        return $this->render('admin/top_menu/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_top_menu_edit', methods: ['GET', 'POST'])]
    public function edit(
        TopMenuItem $menuItem,
        Request $request,
        EntityManagerInterface $entityManager,
        TopMenuItemRepository $topMenuItemRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleRepository $articleRepository,
        UserLanguageResolver $userLanguageResolver,
        TopMenuItemUniqueNameGenerator $topMenuItemUniqueNameGenerator,
        TopMenuCacheManager $topMenuCacheManager,
    ): Response {
        $originalParentId = $menuItem->getParent()?->getId();
        $form = $this->createEditorForm($menuItem, $topMenuItemRepository, $articleCategoryRepository, $articleRepository, $userLanguageResolver);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->syncTranslations($menuItem, $form);
            $menuItem->normalizeTargetConfiguration();
            $this->refreshUniqueNameIfMissing($menuItem, $topMenuItemUniqueNameGenerator);
            $this->addUniqueNameErrorIfStillMissing($form, $menuItem, $userLanguageResolver);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $topMenuItemRepository->applySiblingPositioning(
                $menuItem,
                $originalParentId,
                $originalParentId !== $menuItem->getParent()?->getId(),
            );
            $entityManager->flush();
            $topMenuCacheManager->refresh();

            $this->addFlash('success', $userLanguageResolver->translate('Element menu został zaktualizowany.', 'Menu item updated.'));

            return $this->redirectToRoute('admin_top_menu_index');
        }

        return $this->render('admin/top_menu/edit.html.twig', [
            'menu_item' => $menuItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_top_menu_delete', methods: ['POST'])]
    public function delete(
        TopMenuItem $menuItem,
        Request $request,
        EntityManagerInterface $entityManager,
        TopMenuItemRepository $topMenuItemRepository,
        UserLanguageResolver $userLanguageResolver,
        TopMenuCacheManager $topMenuCacheManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_top_menu_item_'.$menuItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $redirectRoute = 'tree' === $request->request->get('redirect_to') ? 'admin_top_menu_tree' : 'admin_top_menu_index';
        if (!$menuItem->getChildren()->isEmpty()) {
            $this->addFlash('error', $userLanguageResolver->translate('Nie można usunąć elementu menu, który ma dzieci.', 'You cannot delete a menu item that still has children.'));

            return $this->redirectToRoute($redirectRoute);
        }

        $topMenuItemRepository->normalizeSiblingPositions($menuItem->getParent()?->getId(), $menuItem->getId());
        $entityManager->remove($menuItem);
        $entityManager->flush();
        $topMenuCacheManager->refresh();

        $this->addFlash('success', $userLanguageResolver->translate('Element menu został usunięty.', 'Menu item deleted.'));

        return $this->redirectToRoute($redirectRoute);
    }

    #[Route('/export', name: 'admin_top_menu_export', methods: ['POST'])]
    public function export(
        Request $request,
        TopMenuExportQueueRepository $topMenuExportQueueRepository,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('export_top_menu', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$topMenuExportQueueRepository->enqueuePending($this->resolveAuthenticatedUser())) {
            $this->addFlash('success', $userLanguageResolver->translate('Eksport top menu jest już w kolejce.', 'Top menu export is already queued.'));

            return $this->redirectToRoute('admin_top_menu_index');
        }

        $this->addFlash('success', $userLanguageResolver->translate('Eksport top menu został dodany do kolejki.', 'Top menu export added to the queue.'));

        return $this->redirectToRoute('admin_top_menu_index');
    }

    #[Route('/tree/reorder', name: 'admin_top_menu_tree_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        TopMenuItemRepository $topMenuItemRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
        TopMenuCacheManager $topMenuCacheManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('reorder_top_menu_tree', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse([
                'message' => $userLanguageResolver->translate('Nieprawidłowe dane do zmiany kolejności menu.', 'Invalid payload for menu reordering.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $parentId = isset($payload['parentId']) && is_numeric($payload['parentId']) ? (int) $payload['parentId'] : null;
        $orderedIds = $payload['orderedIds'] ?? null;
        if (!is_array($orderedIds) || !$topMenuItemRepository->reorderSiblings($parentId, $orderedIds)) {
            return new JsonResponse([
                'message' => $userLanguageResolver->translate('Nie udało się zapisać nowej kolejności elementów menu.', 'The new menu item order could not be saved.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();
        $topMenuCacheManager->refresh();

        return new JsonResponse([
            'message' => $userLanguageResolver->translate('Nowa kolejność elementów menu została zapisana.', 'The new menu item order has been saved.'),
        ]);
    }

    private function createEditorForm(
        TopMenuItem $menuItem,
        TopMenuItemRepository $topMenuItemRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleRepository $articleRepository,
        UserLanguageResolver $userLanguageResolver,
    ): FormInterface {
        $parentItems = array_values(array_filter(
            $topMenuItemRepository->findForAdminIndex(),
            static fn (TopMenuItem $candidate): bool => $candidate !== $menuItem && null === $candidate->getParent(),
        ));

        return $this->createForm(TopMenuItemType::class, $menuItem, [
            'admin_language' => $userLanguageResolver->getLanguage(),
            'parent_items' => $parentItems,
            'article_categories' => $articleCategoryRepository->findActiveOrderedByName(),
            'articles' => $articleRepository->findRecentForTopMenuSelection(),
        ]);
    }

    private function syncTranslations(TopMenuItem $menuItem, FormInterface $form): void
    {
        /** @var array<string, mixed> $labels */
        $labels = $form->get('labels')->getData();

        $menuItem->setLabels($labels);
    }

    private function refreshUniqueNameIfMissing(TopMenuItem $menuItem, TopMenuItemUniqueNameGenerator $topMenuItemUniqueNameGenerator): void
    {
        if ('' !== trim($menuItem->getUniqueName())) {
            return;
        }

        $baseValue = $menuItem->getLabel('pl', null) ?? $menuItem->getLocalizedLabel('pl');
        if ('' === trim($baseValue)) {
            return;
        }

        $topMenuItemUniqueNameGenerator->refreshUniqueName($menuItem);
    }

    private function addUniqueNameErrorIfStillMissing(FormInterface $form, TopMenuItem $menuItem, UserLanguageResolver $userLanguageResolver): void
    {
        if ('' !== trim($menuItem->getUniqueName())) {
            return;
        }

        $form->addError(new FormError($userLanguageResolver->translate(
            'Nie udało się wygenerować unikalnej nazwy elementu menu.',
            'Failed to generate a unique menu item name.'
        )));
    }
}
