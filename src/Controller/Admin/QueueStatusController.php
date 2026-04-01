<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleExportQueue;
use App\Entity\CategoryExportQueue;
use App\Entity\ArticleImportQueue;
use App\Entity\TopMenuImportQueue;
use App\Entity\TopMenuExportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportType;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\TopMenuImportQueueRepository;
use App\Repository\TopMenuExportQueueRepository;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedFilePathResolver;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/queues')]
class QueueStatusController extends AbstractController
{
    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
        private readonly ManagedFileDeleter $managedFileDeleter,
    ) {
    }

    #[Route('/status', name: 'admin_queue_status', methods: ['GET'])]
    public function index(
        ArticleExportQueueRepository $articleExportQueueRepository,
        CategoryExportQueueRepository $categoryExportQueueRepository,
        TopMenuExportQueueRepository $topMenuExportQueueRepository,
        ArticleImportQueueRepository $articleImportQueueRepository,
        TopMenuImportQueueRepository $topMenuImportQueueRepository,
    ): Response
    {
        $pendingExportQueueItems = [
            ...array_map(
                static fn (ArticleExportQueue $queueItem): array => [
                    'id' => $queueItem->getId(),
                    'type' => ArticleExportType::ARTICLES,
                    'label' => $queueItem->getArticle()->getTitle(),
                    'edit_route' => 'admin_article_edit',
                    'edit_route_params' => ['id' => $queueItem->getArticle()->getId()],
                    'requested_by' => $queueItem->getRequestedBy(),
                    'created_at' => $queueItem->getCreatedAt(),
                    'delete_route' => 'admin_queue_status_export_delete',
                    'csrf_token_id' => 'delete_queue_item_'.$queueItem->getId(),
                ],
                $articleExportQueueRepository->findPendingOrderedByCreatedAt(),
            ),
            ...array_map(
                static fn (CategoryExportQueue $queueItem): array => [
                    'id' => $queueItem->getId(),
                    'type' => ArticleExportType::CATEGORIES,
                    'label' => $queueItem->getCategory()->getName(),
                    'edit_route' => 'admin_article_category_edit',
                    'edit_route_params' => ['id' => $queueItem->getCategory()->getId()],
                    'requested_by' => $queueItem->getRequestedBy(),
                    'created_at' => $queueItem->getCreatedAt(),
                    'delete_route' => 'admin_queue_status_category_export_delete',
                    'csrf_token_id' => 'delete_category_export_queue_item_'.$queueItem->getId(),
                ],
                $categoryExportQueueRepository->findPendingOrderedByCreatedAt(),
            ),
            ...array_map(
                static fn (TopMenuExportQueue $queueItem): array => [
                    'id' => $queueItem->getId(),
                    'type' => ArticleExportType::TOP_MENU,
                    'label' => 'Cała hierarchia menu',
                    'label_key' => 'admin_top_menu_export_queue_label',
                    'edit_route' => 'admin_top_menu_index',
                    'edit_route_params' => [],
                    'requested_by' => $queueItem->getRequestedBy(),
                    'created_at' => $queueItem->getCreatedAt(),
                    'delete_route' => 'admin_queue_status_top_menu_export_delete',
                    'csrf_token_id' => 'delete_top_menu_export_queue_item_'.$queueItem->getId(),
                ],
                $topMenuExportQueueRepository->findPendingOrderedByCreatedAt(),
            ),
        ];
        usort(
            $pendingExportQueueItems,
            static fn (array $left, array $right): int => $left['created_at'] <=> $right['created_at']
        );
        $pendingImportQueueItems = [
            ...array_map(
                static fn (ArticleImportQueue $queueItem): array => [
                    'id' => $queueItem->getId(),
                    'type_key' => 'admin_queue_type_article_import',
                    'type_label' => 'Import artykułu',
                    'original_filename' => $queueItem->getOriginalFilename(),
                    'file_path' => $queueItem->getFilePath(),
                    'requested_by' => $queueItem->getRequestedBy(),
                    'created_at' => $queueItem->getCreatedAt(),
                    'delete_route' => 'admin_queue_status_import_delete',
                    'csrf_token_id' => 'delete_import_queue_item_'.$queueItem->getId(),
                ],
                $articleImportQueueRepository->findPendingOrderedByCreatedAt(),
            ),
            ...array_map(
                static fn (TopMenuImportQueue $queueItem): array => [
                    'id' => $queueItem->getId(),
                    'type_key' => 'admin_queue_type_top_menu_import',
                    'type_label' => 'Import menu',
                    'original_filename' => $queueItem->getOriginalFilename(),
                    'file_path' => $queueItem->getFilePath(),
                    'requested_by' => $queueItem->getRequestedBy(),
                    'created_at' => $queueItem->getCreatedAt(),
                    'delete_route' => 'admin_queue_status_top_menu_import_delete',
                    'csrf_token_id' => 'delete_top_menu_import_queue_item_'.$queueItem->getId(),
                ],
                $topMenuImportQueueRepository->findPendingOrderedByCreatedAt(),
            ),
        ];
        usort(
            $pendingImportQueueItems,
            static fn (array $left, array $right): int => $left['created_at'] <=> $right['created_at']
        );

        return $this->render('admin/queue_status/index.html.twig', [
            'pending_export_queue_items' => $pendingExportQueueItems,
            'pending_import_queue_items' => $pendingImportQueueItems,
            'has_pending_queue_items' => $articleExportQueueRepository->countPending() + $categoryExportQueueRepository->countPending() + $topMenuExportQueueRepository->countPending() + $articleImportQueueRepository->countPending() + $topMenuImportQueueRepository->countPending() > 0,
        ]);
    }

    #[Route('/status/clear', name: 'admin_queue_status_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleExportQueueRepository $articleExportQueueRepository,
        CategoryExportQueueRepository $categoryExportQueueRepository,
        TopMenuExportQueueRepository $topMenuExportQueueRepository,
        ArticleImportQueueRepository $articleImportQueueRepository,
        TopMenuImportQueueRepository $topMenuImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_queue_items', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        foreach ($articleExportQueueRepository->findBy(['status' => ArticleExportQueueStatus::PENDING]) as $queueItem) {
            $entityManager->remove($queueItem);
        }
        foreach ($categoryExportQueueRepository->findBy(['status' => ArticleExportQueueStatus::PENDING]) as $queueItem) {
            $entityManager->remove($queueItem);
        }
        foreach ($topMenuExportQueueRepository->findBy(['status' => ArticleExportQueueStatus::PENDING]) as $queueItem) {
            $entityManager->remove($queueItem);
        }

        $pendingImportQueueItems = $articleImportQueueRepository->findBy(['status' => ArticleImportQueueStatus::PENDING]);
        $pathsToDelete = [];
        foreach ($pendingImportQueueItems as $queueItem) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($pendingImportQueueItems as $queueItem) {
            $entityManager->remove($queueItem);
        }

        $pendingTopMenuImportQueueItems = $topMenuImportQueueRepository->findBy(['status' => ArticleImportQueueStatus::PENDING]);
        $pathsToDelete = [];
        foreach ($pendingTopMenuImportQueueItems as $queueItem) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($pendingTopMenuImportQueueItems as $queueItem) {
            $entityManager->remove($queueItem);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Kolejka oczekujących elementów została wyczyszczona.', 'The pending queue has been cleared.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/top-menu-exports/{id}/delete', name: 'admin_queue_status_top_menu_export_delete', methods: ['POST'])]
    public function deleteTopMenuExport(
        TopMenuExportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_top_menu_export_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/exports/{id}/delete', name: 'admin_queue_status_export_delete', methods: ['POST'])]
    public function deleteExport(
        ArticleExportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/category-exports/{id}/delete', name: 'admin_queue_status_category_export_delete', methods: ['POST'])]
    public function deleteCategoryExport(
        CategoryExportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_category_export_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/imports/{id}/delete', name: 'admin_queue_status_import_delete', methods: ['POST'])]
    public function deleteImport(
        ArticleImportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_import_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/top-menu-imports/{id}/delete', name: 'admin_queue_status_top_menu_import_delete', methods: ['POST'])]
    public function deleteTopMenuImport(
        TopMenuImportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_top_menu_import_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }
}
