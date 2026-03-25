<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleExport;
use App\Enum\ArticleExportStatus;
use App\Repository\ArticleExportRepository;
use App\Service\ManagedFilePathResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/exports')]
class ArticleExportController extends AbstractController
{
    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    #[Route('', name: 'admin_article_export_index', methods: ['GET'])]
    public function index(ArticleExportRepository $articleExportRepository): Response
    {
        return $this->render('admin/article_export/index.html.twig', [
            'exports' => $articleExportRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_article_export_download', methods: ['GET'])]
    public function download(ArticleExport $articleExport, EntityManagerInterface $entityManager): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveExportPath($articleExport->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', 'Plik eksportu nie jest dostępny do pobrania.');

            return $this->redirectToRoute('admin_article_export_index');
        }

        if (ArticleExportStatus::DOWNLOADED !== $articleExport->getStatus()) {
            $articleExport->setStatus(ArticleExportStatus::DOWNLOADED);
            $entityManager->flush();
        }

        $response = new BinaryFileResponse(
            $absolutePath,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true,
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            false,
            false
        );

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($absolutePath)
        );

        return $response;
    }

    #[Route('/{id}/delete', name: 'admin_article_export_delete', methods: ['POST'])]
    public function delete(
        ArticleExport $articleExport,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_export_'.$articleExport->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveExportPath($articleExport->getFilePath());
        $this->deleteExportFile($absolutePath);
        $entityManager->remove($articleExport);
        $entityManager->flush();

        $this->addFlash('success', 'Eksport został usunięty.');

        return $this->redirectToRoute('admin_article_export_index');
    }

    #[Route('/clear', name: 'admin_article_export_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleExportRepository $articleExportRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_article_exports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $articleExports = $articleExportRepository->findBy([]);
        $pathsToDelete = [];
        foreach ($articleExports as $articleExport) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveExportPath($articleExport->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->deleteExportFile($path);
        }

        foreach ($articleExports as $articleExport) {
            $entityManager->remove($articleExport);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Wszystkie eksporty zostały usunięte.');

        return $this->redirectToRoute('admin_article_export_index');
    }

    private function deleteExportFile(?string $absolutePath): void
    {
        if (null !== $absolutePath && is_file($absolutePath)) {
            if (!unlink($absolutePath)) {
                throw new \RuntimeException(sprintf('Failed to delete export file: %s', $absolutePath));
            }
        }
    }
}
