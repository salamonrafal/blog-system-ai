<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleImportQueue;
use App\Form\ArticleImportType;
use App\Repository\ArticleImportQueueRepository;
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

#[Route('/admin/imports')]
class ArticleImportController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
        private readonly ManagedFileDeleter $managedFileDeleter,
    ) {
    }

    #[Route('', name: 'admin_article_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleImportQueueRepository $articleImportQueueRepository,
        ArticleImportStorage $articleImportStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(ArticleImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('importFile')->getData();
            $storedFile = $articleImportStorage->store($uploadedFile);

            $queueItem = (new ArticleImportQueue())
                ->setOriginalFilename($storedFile['original_filename'])
                ->setFilePath($storedFile['relative_path'])
                ->setRequestedBy($this->resolveAuthenticatedUser());

            $entityManager->persist($queueItem);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Plik importu został dodany do kolejki.', 'The import file has been added to the queue.'));

            return $this->redirectToRoute('admin_article_import_index');
        }

        return $this->render('admin/article_import/index.html.twig', [
            'form' => $form,
            'imports' => $articleImportQueueRepository->findAllForAdminIndex(),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_article_import_download', methods: ['GET'])]
    public function download(ArticleImportQueue $articleImportQueue, UserLanguageResolver $userLanguageResolver): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($articleImportQueue->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', $userLanguageResolver->translate('Plik importu nie jest dostępny do pobrania.', 'The import file is not available for download.'));

            return $this->redirectToRoute('admin_article_import_index');
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

    #[Route('/{id}/delete', name: 'admin_article_import_delete', methods: ['POST'])]
    public function delete(
        ArticleImportQueue $articleImportQueue,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_import_'.$articleImportQueue->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($articleImportQueue->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($articleImportQueue);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Import został usunięty.', 'The import has been deleted.'));

        return $this->redirectToRoute('admin_article_import_index');
    }

    #[Route('/clear', name: 'admin_article_import_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleImportQueueRepository $articleImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_article_imports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $articleImports = $articleImportQueueRepository->findBy([]);
        $pathsToDelete = [];
        foreach ($articleImports as $articleImport) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($articleImport->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($articleImports as $articleImport) {
            $entityManager->remove($articleImport);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Wszystkie importy zostały usunięte.', 'All imports have been deleted.'));

        return $this->redirectToRoute('admin_article_import_index');
    }
}
