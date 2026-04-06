<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MediaImage;
use App\Form\MediaImageUploadType;
use App\Repository\MediaImageRepository;
use App\Service\MediaGalleryManager;
use App\Service\MediaImageStorage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserLanguageResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormInterface;

#[Route('/admin/media')]
class MediaController extends AbstractController
{
    use AuthenticatedAdminUserTrait;

    #[Route('', name: 'admin_media_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MediaImageStorage $mediaImageStorage,
        MediaGalleryManager $mediaGalleryManager,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(MediaImageUploadType::class);
        $response = $this->handleUploadForm($form, $request, $mediaImageStorage, $mediaGalleryManager, $entityManager, $userLanguageResolver);
        if (null !== $response) {
            return $response;
        }

        return $this->render('admin/media/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/gallery', name: 'admin_media_gallery', methods: ['GET', 'POST'])]
    public function gallery(
        Request $request,
        MediaImageRepository $mediaImageRepository,
        MediaImageStorage $mediaImageStorage,
        MediaGalleryManager $mediaGalleryManager,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        $form = $this->createForm(MediaImageUploadType::class);
        $response = $this->handleUploadForm($form, $request, $mediaImageStorage, $mediaGalleryManager, $entityManager, $userLanguageResolver);
        if (null !== $response) {
            return $response;
        }

        return $this->render('admin/media/gallery.html.twig', [
            'gallery_images' => $mediaImageRepository->findAllForAdminIndex(),
            'upload_form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(
        MediaImage $mediaImage,
        Request $request,
        MediaGalleryManager $mediaGalleryManager,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_media_'.$mediaImage->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $entityManager->remove($mediaImage);
        $entityManager->flush();

        $this->addFlash('success', $userLanguageResolver->translate('Obrazek został usunięty z galerii.', 'The image has been removed from the gallery.'));
        try {
            $mediaGalleryManager->delete($mediaImage);
        } catch (\Throwable) {
            $this->addFlash('error', $userLanguageResolver->translate('Obrazek został usunięty z galerii, ale nie udało się usunąć pliku z dysku.', 'The image was removed from the gallery, but the file could not be deleted from disk.'));
        }

        return $this->redirectToRoute('admin_media_gallery');
    }

    #[Route('/clear', name: 'admin_media_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        MediaImageRepository $mediaImageRepository,
        MediaGalleryManager $mediaGalleryManager,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('clear_media_gallery', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $mediaImages = $mediaImageRepository->findBy([]);
        foreach ($mediaImages as $mediaImage) {
            $entityManager->remove($mediaImage);
        }
        $entityManager->flush();
        $failedFilePaths = $mediaGalleryManager->clear($mediaImages);

        $this->addFlash('success', $userLanguageResolver->translate('Galeria została wyczyszczona.', 'The gallery has been cleared.'));
        if ([] !== $failedFilePaths) {
            $this->addFlash('error', $userLanguageResolver->translate('Galeria została wyczyszczona, ale nie udało się usunąć wszystkich plików z dysku.', 'The gallery was cleared, but not all files could be deleted from disk.'));
        }

        return $this->redirectToRoute('admin_media_gallery');
    }

    #[Route('/{id}/rename', name: 'admin_media_rename', methods: ['POST'])]
    public function rename(
        MediaImage $mediaImage,
        Request $request,
        MediaImageRepository $mediaImageRepository,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): Response {
        if (!$this->isCsrfTokenValid('rename_media_'.$mediaImage->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $customName = trim((string) $request->request->get('custom_name'));
        if ('' === $customName) {
            $this->addFlash('error', $userLanguageResolver->translate('Podaj niestandardową nazwę obrazka.', 'Provide a custom image name.'));

            return $this->redirectToRoute('admin_media_gallery');
        }

        if (mb_strlen($customName) > 255) {
            $this->addFlash('error', $userLanguageResolver->translate('Niestandardowa nazwa obrazka może mieć maksymalnie 255 znaków.', 'The custom image name can be at most 255 characters long.'));

            return $this->redirectToRoute('admin_media_gallery');
        }

        if ($mediaImageRepository->customNameExists($customName, $mediaImage->getId())) {
            $this->addFlash('error', $userLanguageResolver->translate('Taka niestandardowa nazwa obrazka już istnieje.', 'This custom image name already exists.'));

            return $this->redirectToRoute('admin_media_gallery');
        }

        $mediaImage->setCustomName($customName);
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('error', $userLanguageResolver->translate('Taka niestandardowa nazwa obrazka już istnieje.', 'This custom image name already exists.'));

            return $this->redirectToRoute('admin_media_gallery');
        }

        $this->addFlash('success', $userLanguageResolver->translate('Niestandardowa nazwa obrazka została zapisana.', 'The custom image name has been saved.'));

        return $this->redirectToRoute('admin_media_gallery');
    }

    private function handleUploadForm(
        FormInterface $form,
        Request $request,
        MediaImageStorage $mediaImageStorage,
        MediaGalleryManager $mediaGalleryManager,
        EntityManagerInterface $entityManager,
        UserLanguageResolver $userLanguageResolver,
    ): ?Response {
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return null;
        }

        if (!$form->isValid()) {
            $this->addFlash('error', $userLanguageResolver->translate('Nie udało się dodać obrazka. Sprawdź błędy formularza.', 'The image could not be added. Check the form errors.'));

            return null;
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('imageFile')->getData();
        $storedFile = $mediaImageStorage->store($uploadedFile);

        $mediaImage = (new MediaImage())
            ->setOriginalFilename($storedFile['original_filename'])
            ->setFilePath($storedFile['relative_path'])
            ->setFileSize($storedFile['file_size'])
            ->setMimeType($storedFile['mime_type'])
            ->setRequestedBy($this->resolveAuthenticatedUser());

        $entityManager->persist($mediaImage);
        try {
            $entityManager->flush();
        } catch (\Throwable $exception) {
            try {
                $mediaGalleryManager->delete($mediaImage);
            } catch (\Throwable) {
            }

            throw $exception;
        }

        $this->addFlash('success', $userLanguageResolver->translate('Obrazek został dodany do galerii.', 'The image has been added to the gallery.'));

        return $this->redirectToRoute('admin_media_gallery');
    }
}
