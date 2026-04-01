<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CategoryImportQueue;
use App\Form\ArticleImportType;
use App\Repository\CategoryImportQueueRepository;
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

#[Route('/admin/category-imports')]
class CategoryImportController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
        private readonly ManagedFileDeleter $managedFileDeleter,
    ) {
    }

    #[Route('', name: 'admin_category_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryImportQueueRepository $categoryImportQueueRepository,
        ArticleImportStorage $articleImportStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(ArticleImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('importFile')->getData();
            $storedFile = $articleImportStorage->store($uploadedFile, 'category-import');

            $queueItem = (new CategoryImportQueue())
                ->setOriginalFilename($storedFile['original_filename'])
                ->setFilePath($storedFile['relative_path'])
                ->setRequestedBy($this->resolveAuthenticatedUser());

            $entityManager->persist($queueItem);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Plik importu kategorii został dodany do kolejki.', 'The category import file has been added to the queue.'));

            return $this->redirectToRoute('admin_category_import_index');
        }

        return $this->render('admin/category_import/index.html.twig', [
            'form' => $form,
            'imports' => $categoryImportQueueRepository->findAllForAdminIndex(),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_category_import_download', methods: ['GET'])]
    public function download(CategoryImportQueue $categoryImportQueue, UserLanguageResolver $userLanguageResolver): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($categoryImportQueue->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', $userLanguageResolver->translate('Plik importu kategorii nie jest dostępny do pobrania.', 'The category import file is not available for download.'));

            return $this->redirectToRoute('admin_category_import_index');
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

    #[Route('/{id}/delete', name: 'admin_category_import_delete', methods: ['POST'])]
    public function delete(
        CategoryImportQueue $categoryImportQueue,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_category_import_'.$categoryImportQueue->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($categoryImportQueue->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($categoryImportQueue);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Import kategorii został usunięty.', 'The category import has been deleted.'));

        return $this->redirectToRoute('admin_category_import_index');
    }

    #[Route('/clear', name: 'admin_category_import_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        CategoryImportQueueRepository $categoryImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_category_imports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $categoryImports = $categoryImportQueueRepository->findBy([]);
        $pathsToDelete = [];
        foreach ($categoryImports as $categoryImport) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($categoryImport->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($categoryImports as $categoryImport) {
            $entityManager->remove($categoryImport);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Wszystkie importy kategorii zostały usunięte.', 'All category imports have been deleted.'));

        return $this->redirectToRoute('admin_category_import_index');
    }
}
