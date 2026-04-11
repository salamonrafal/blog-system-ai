<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleKeywordImportQueue;
use App\Form\ArticleImportType;
use App\Repository\ArticleKeywordImportQueueRepository;
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

#[Route('/admin/article-keyword-imports')]
class ArticleKeywordImportController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    public function __construct(
        private readonly ManagedFilePathResolver $managedFilePathResolver,
        private readonly ManagedFileDeleter $managedFileDeleter,
    ) {
    }

    #[Route('', name: 'admin_article_keyword_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleKeywordImportQueueRepository $articleKeywordImportQueueRepository,
        ArticleImportStorage $articleImportStorage,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(ArticleImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('importFile')->getData();
            $storedFile = $articleImportStorage->store($uploadedFile, 'article-keyword-import');

            $queueItem = (new ArticleKeywordImportQueue())
                ->setOriginalFilename($storedFile['original_filename'])
                ->setFilePath($storedFile['relative_path'])
                ->setRequestedBy($this->resolveAuthenticatedUser());

            $entityManager->persist($queueItem);
            $entityManager->flush();

            $this->addFlash('success', $userLanguageResolver->translate('Plik importu słów kluczowych został dodany do kolejki.', 'The keyword import file has been added to the queue.'));

            return $this->redirectToRoute('admin_article_keyword_import_index');
        }

        return $this->render('admin/article_keyword/import.html.twig', [
            'form' => $form,
            'imports' => $articleKeywordImportQueueRepository->findAllForAdminIndex(),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_article_keyword_import_download', methods: ['GET'])]
    public function download(ArticleKeywordImportQueue $articleKeywordImportQueue, UserLanguageResolver $userLanguageResolver): Response
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($articleKeywordImportQueue->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', $userLanguageResolver->translate('Plik importu słów kluczowych nie jest dostępny do pobrania.', 'The keyword import file is not available for download.'));

            return $this->redirectToRoute('admin_article_keyword_import_index');
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

    #[Route('/{id}/delete', name: 'admin_article_keyword_import_delete', methods: ['POST'])]
    public function delete(
        ArticleKeywordImportQueue $articleKeywordImportQueue,
        Request $request,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_keyword_import_'.$articleKeywordImportQueue->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->managedFilePathResolver->resolveImportPath($articleKeywordImportQueue->getFilePath());
        $this->managedFileDeleter->delete($absolutePath, 'import');
        $entityManager->remove($articleKeywordImportQueue);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Import słów kluczowych został usunięty.', 'The keyword import has been deleted.'));

        return $this->redirectToRoute('admin_article_keyword_import_index');
    }

    #[Route('/clear', name: 'admin_article_keyword_import_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleKeywordImportQueueRepository $articleKeywordImportQueueRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_article_keyword_imports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $keywordImports = $articleKeywordImportQueueRepository->findBy([]);
        $pathsToDelete = [];
        foreach ($keywordImports as $keywordImport) {
            $pathsToDelete[] = $this->managedFilePathResolver->resolveImportPath($keywordImport->getFilePath());
        }

        foreach ($pathsToDelete as $path) {
            $this->managedFileDeleter->delete($path, 'import');
        }

        foreach ($keywordImports as $keywordImport) {
            $entityManager->remove($keywordImport);
        }

        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Wszystkie importy słów kluczowych zostały usunięte.', 'All keyword imports have been deleted.'));

        return $this->redirectToRoute('admin_article_keyword_import_index');
    }
}
