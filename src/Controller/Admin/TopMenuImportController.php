<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\TopMenuImportQueue;
use App\Form\ArticleImportType;
use App\Repository\TopMenuImportQueueRepository;
use App\Service\ArticleImportStorage;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedFilePathResolver;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/top-menu/imports')]
class TopMenuImportController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
        private readonly ManagedFileDeleter $managedFileDeleter,
    ) {
    }

    #[Route('', name: 'admin_top_menu_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        TopMenuImportQueueRepository $topMenuImportQueueRepository,
        ArticleImportStorage $articleImportStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(ArticleImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('importFile')->getData();
            $storedFile = $articleImportStorage->store($uploadedFile, 'top-menu-import');

            $queueItem = (new TopMenuImportQueue())
                ->setOriginalFilename($storedFile['original_filename'])
                ->setFilePath($storedFile['relative_path'])
                ->setRequestedBy($this->resolveAuthenticatedUser());

            $entityManager->persist($queueItem);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Plik importu menu został dodany do kolejki.', 'The top menu import file has been added to the queue.'));

            return $this->redirectToRoute('admin_top_menu_import_index');
        }

        return $this->render('admin/top_menu/import.html.twig', [
            'form' => $form,
            'imports' => $topMenuImportQueueRepository->findAllForAdminIndex(),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_top_menu_import_download', methods: ['GET'])]
    public function download(TopMenuImportQueue $topMenuImportQueue, UserLanguageResolver $userLanguageResolver): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($topMenuImportQueue->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', $userLanguageResolver->translate('Plik importu menu nie jest dostępny do pobrania.', 'The top menu import file is not available for download.'));

            return $this->redirectToRoute('admin_top_menu_import_index');
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

    #[Route('/{id}/delete', name: 'admin_top_menu_import_delete', methods: ['POST'])]
    public function delete(
        TopMenuImportQueue $topMenuImportQueue,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_top_menu_import_'.$topMenuImportQueue->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($topMenuImportQueue->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($topMenuImportQueue);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Import menu został usunięty.', 'The top menu import has been deleted.'));

        return $this->redirectToRoute('admin_top_menu_import_index');
    }

    #[Route('/clear', name: 'admin_top_menu_import_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        TopMenuImportQueueRepository $topMenuImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_top_menu_imports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $topMenuImports = $topMenuImportQueueRepository->findBy([]);
        $pathsToDelete = [];
        foreach ($topMenuImports as $topMenuImport) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($topMenuImport->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($topMenuImports as $topMenuImport) {
            $entityManager->remove($topMenuImport);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Wszystkie importy menu zostały usunięte.', 'All top menu imports have been deleted.'));

        return $this->redirectToRoute('admin_top_menu_import_index');
    }
}
