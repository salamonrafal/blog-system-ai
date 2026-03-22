<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleExportQueue;
use App\Repository\ArticleExportQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/queues')]
class QueueStatusController extends AbstractController
{
    #[Route('/status', name: 'admin_queue_status', methods: ['GET'])]
    public function index(ArticleExportQueueRepository $articleExportQueueRepository): Response
    {
        return $this->render('admin/queue_status/index.html.twig', [
            'pending_queue_items' => $articleExportQueueRepository->findPendingOrderedByCreatedAt(),
        ]);
    }

    #[Route('/status/clear', name: 'admin_queue_status_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleExportQueueRepository $articleExportQueueRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_queue_items', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        foreach ($articleExportQueueRepository->findPendingOrderedByCreatedAt() as $queueItem) {
            $entityManager->remove($queueItem);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Kolejka oczekujacych elementow zostala wyczyszczona.');

        return $this->redirectToRoute('admin_queue_status');
    }

    #[Route('/{id}/delete', name: 'admin_queue_status_delete', methods: ['POST'])]
    public function delete(
        ArticleExportQueue $queueItem,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_queue_item_'.$queueItem->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($queueItem);
        $entityManager->flush();

        $this->addFlash('success', 'Element zostal usuniety z kolejki.');

        return $this->redirectToRoute('admin_queue_status');
    }
}
