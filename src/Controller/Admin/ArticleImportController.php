<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArticleImportQueue;
use App\Form\ArticleImportType;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/imports')]
class ArticleImportController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%app.article_import_directory%')]
        private readonly string $importDirectory,
    ) {
    }

    #[Route('', name: 'admin_article_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleImportQueueRepository $articleImportQueueRepository,
        ArticleImportStorage $articleImportStorage,
    ): Response {
        $form = $this->createForm(ArticleImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('importFile')->getData();
            $storedFile = $articleImportStorage->store($uploadedFile);

            $queueItem = (new ArticleImportQueue())
                ->setOriginalFilename($storedFile['original_filename'])
                ->setFilePath($storedFile['relative_path']);

            $entityManager->persist($queueItem);
            $entityManager->flush();

            $this->addFlash('success', 'Plik importu został dodany do kolejki.');

            return $this->redirectToRoute('admin_article_import_index');
        }

        return $this->render('admin/article_import/index.html.twig', [
            'form' => $form,
            'imports' => $articleImportQueueRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_article_import_download', methods: ['GET'])]
    public function download(ArticleImportQueue $articleImportQueue): Response
    {
        $absolutePath = $this->resolveImportPath($articleImportQueue);
        if (null === $absolutePath || !is_file($absolutePath)) {
            $this->addFlash('error', 'Plik importu nie jest dostępny do pobrania.');

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
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_import_'.$articleImportQueue->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $absolutePath = $this->resolveImportPath($articleImportQueue);

        $entityManager->remove($articleImportQueue);
        $entityManager->flush();

        $this->deleteImportFile($absolutePath);

        $this->addFlash('success', 'Import został usunięty.');

        return $this->redirectToRoute('admin_article_import_index');
    }

    #[Route('/clear', name: 'admin_article_import_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        ArticleImportQueueRepository $articleImportQueueRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_article_imports', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $pathsToDelete = [];
        foreach ($articleImportQueueRepository->findBy([]) as $articleImport) {
            $pathsToDelete[] = $this->resolveImportPath($articleImport);
            $entityManager->remove($articleImport);
        }

        $entityManager->flush();

        foreach ($pathsToDelete as $path) {
            $this->deleteImportFile($path);
        }

        $this->addFlash('success', 'Wszystkie importy zostały usunięte.');

        return $this->redirectToRoute('admin_article_import_index');
    }

    private function resolveImportPath(ArticleImportQueue $articleImportQueue): ?string
    {
        $relativePath = ltrim($articleImportQueue->getFilePath(), '/');
        $absolutePath = $this->projectDir.'/'.$relativePath;
        $realProjectDir = realpath($this->projectDir);
        $realPath = realpath($absolutePath);
        $realImportDirectory = realpath($this->projectDir.'/'.trim($this->importDirectory, '/'));

        if (false === $realProjectDir || false === $realPath || false === $realImportDirectory) {
            return null;
        }

        $normalizedProjectDir = rtrim($realProjectDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $normalizedImportDirectory = rtrim($realImportDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!str_starts_with($realPath, $normalizedProjectDir) || !str_starts_with($realPath, $normalizedImportDirectory)) {
            return null;
        }

        return $realPath;
    }

    private function deleteImportFile(?string $absolutePath): void
    {
        if (null !== $absolutePath && is_file($absolutePath)) {
            if (!unlink($absolutePath)) {
                throw new \RuntimeException(sprintf('Failed to delete import file: %s', $absolutePath));
            }
        }
    }
}
