<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleExportQueue;
use App\Entity\ArticleImportQueue;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleImportQueueStatus;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleImportQueueRepository;
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
        ArticleImportQueueRepository $articleImportQueueRepository,
    ): Response
    {
        $pendingExportQueueItems = $articleExportQueueRepository->findPendingOrderedByCreatedAt();
        $pendingImportQueueItems = $articleImportQueueRepository->findPendingOrderedByCreatedAt();

        return $this->render('admin/queue_status/index.html.twig', [
            'pending_export_queue_items' => $pendingExportQueueItems,
            'pending_import_queue_items' => $pendingImportQueueItems,
            'has_pending_queue_items' => $articleExportQueueRepository->countPending() + $articleImportQueueRepository->countPending() > 0,
        ]);
    }

    #[Route('/status/clear', name: 'admin_queue_status_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleExportQueueRepository $articleExportQueueRepository,
        ArticleImportQueueRepository $articleImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_queue_items', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        foreach ($articleExportQueueRepository->findBy(['status' => ArticleExportQueueStatus::PENDING]) as $queueItem) {
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

        $entityManager->flush();

        $this->addFlash('success', $this->translateFlash($userLanguageResolver, 'Kolejka oczekujących elementów została wyczyszczona.', 'The pending queue has been cleared.'));

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

        $this->addFlash('success', $this->translateFlash($userLanguageResolver, 'Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

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

        $this->addFlash('success', $this->translateFlash($userLanguageResolver, 'Element został usunięty z kolejki.', 'The item has been removed from the queue.'));

        return $this->redirectToRoute('admin_queue_status');
    }

    private function translateFlash(UserLanguageResolver $userLanguageResolver, string $polish, string $english): string
    {
        return 'pl' === $userLanguageResolver->getLanguage() ? $polish : $english;
    }
}
