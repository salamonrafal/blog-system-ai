<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleExport;
use App\Enum\ArticleExportType;
use App\Enum\ArticleExportStatus;
use App\Repository\ArticleExportRepository;
use App\Service\ArticleExportFileWriter;
use App\Service\BlogSettingsProvider;
use App\Service\ManagedFilePathResolver;
use App\Service\PaginationBuilder;
use App\Service\UserLanguageResolver;
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
        private readonly ArticleExportFileWriter $articleExportFileWriter,
    ) {
    }

    #[Route('', name: 'admin_article_export_index', methods: ['GET'])]
    public function index(
        Request $request,
        ArticleExportRepository $articleExportRepository,
        BlogSettingsProvider $blogSettingsProvider,
        PaginationBuilder $paginationBuilder,
    ): Response
    {
        $selectedType = $this->resolveSelectedType($request);
        $itemsPerPage = max(1, $blogSettingsProvider->getSettings()->getAdminListingItemsPerPage());
        $requestedPage = max(1, $request->query->getInt('page', 1));
        $totalExports = $articleExportRepository->countForAdminIndex($selectedType);
        $totalPages = max(1, (int) ceil($totalExports / $itemsPerPage));
        $currentPage = min($requestedPage, $totalPages);

        return $this->render('admin/article_export/index.html.twig', [
            'exports' => $articleExportRepository->findPaginatedForAdminIndex($currentPage, $itemsPerPage, $selectedType),
            'selected_type' => $selectedType,
            'export_types' => ArticleExportType::cases(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'pagination_items' => $paginationBuilder->buildPaginationItems($currentPage, $totalPages),
            'pagination_route_params' => array_filter([
                'type' => $selectedType?->value,
            ], static fn (mixed $value): bool => null !== $value && '' !== $value),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_article_export_download', methods: ['GET'])]
    public function download(ArticleExport $articleExport, EntityManagerInterface $entityManager, UserLanguageResolver $userLanguageResolver): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveExportPath($articleExport->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', $userLanguageResolver->translate('Plik eksportu nie jest dostępny do pobrania.', 'The export file is not available for download.'));

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
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_export_'.$articleExport->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->articleExportFileWriter->delete($articleExport->getFilePath());
        $entityManager->remove($articleExport);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Eksport został usunięty.', 'The export has been deleted.'));

        return $this->redirectToRoute('admin_article_export_index');
    }

    #[Route('/clear', name: 'admin_article_export_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleExportRepository $articleExportRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_article_exports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $articleExports = $articleExportRepository->findBy([]);
        foreach ($articleExports as $articleExport) {
            $this->articleExportFileWriter->delete($articleExport->getFilePath());
        }

        foreach ($articleExports as $articleExport) {
            $entityManager->remove($articleExport);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Wszystkie eksporty zostały usunięte.', 'All exports have been deleted.'));

        return $this->redirectToRoute('admin_article_export_index');
    }

    private function resolveSelectedType(Request $request): ?ArticleExportType
    {
        $type = $request->query->get('type');
        if (!is_string($type) || '' === trim($type)) {
            return null;
        }

        return ArticleExportType::tryFrom(trim($type));
    }
}
