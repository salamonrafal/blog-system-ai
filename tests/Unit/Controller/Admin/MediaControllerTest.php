<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\MediaController;
use App\Entity\MediaImage;
use App\Form\MediaImageUploadType;
use App\Repository\MediaImageRepository;
use App\Service\MediaGalleryManager;
use App\Service\MediaImageStorage;
use App\Service\UserLanguageResolver;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validation;

final class MediaControllerTest extends TestCase
{
    public function testIndexRendersGalleryFromManager(): void
    {
        $storage = $this->createMock(MediaImageStorage::class);
        $storage->expects($this->never())->method('store');

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager->expects($this->never())->method('delete');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $controller = new TestMediaController();

        $response = $controller->index(new Request(), $storage, $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/media/index.html.twig', $controller->capturedView);
    }

    public function testGalleryRendersGalleryFromRepository(): void
    {
        $galleryImages = [
            (new MediaImage())
                ->setOriginalFilename('hero.webp')
                ->setCustomName('Hero banner')
                ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
                ->setFileSize(1234)
                ->setMimeType('image/webp'),
        ];

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAllForAdminIndex')
            ->willReturn($galleryImages);

        $controller = new TestMediaController();
        $storage = $this->createMock(MediaImageStorage::class);
        $storage->expects($this->never())->method('store');

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager->expects($this->never())->method('delete');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $response = $controller->gallery(new Request(), $repository, $storage, $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/media/gallery.html.twig', $controller->capturedView);
        $this->assertSame($galleryImages, $controller->capturedParameters['gallery_images']);
        $this->assertInstanceOf(FormInterface::class, $controller->capturedParameters['upload_form']);
    }

    public function testGalleryShowsFeedbackWhenUploadFormIsInvalid(): void
    {
        $galleryImages = [
            (new MediaImage())
                ->setOriginalFilename('hero.webp')
                ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
                ->setFileSize(1234)
                ->setMimeType('image/webp'),
        ];

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAllForAdminIndex')
            ->willReturn($galleryImages);

        $storage = $this->createMock(MediaImageStorage::class);
        $storage->expects($this->never())->method('store');

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager->expects($this->never())->method('delete');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Nie udało się dodać obrazka. Sprawdź błędy formularza.', 'The image could not be added. Check the form errors.')
            ->willReturn('Nie udało się dodać obrazka. Sprawdź błędy formularza.');

        $controller = new TestMediaController();
        $request = new Request([], ['media_image_upload' => []], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->gallery($request, $repository, $storage, $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/media/gallery.html.twig', $controller->capturedView);
        $this->assertInstanceOf(FormInterface::class, $controller->capturedParameters['upload_form']);
        $this->assertTrue($controller->capturedParameters['upload_form']->isSubmitted());
        $this->assertFalse($controller->capturedParameters['upload_form']->isValid());
        $this->assertSame([
            [
                'type' => 'error',
                'message' => 'Nie udało się dodać obrazka. Sprawdź błędy formularza.',
            ],
        ], $controller->capturedFlashes);
    }

    public function testRenameRejectsDuplicateCustomName(): void
    {
        $mediaImage = (new MediaImage())
            ->setOriginalFilename('hero.webp')
            ->setCustomName('Current')
            ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
            ->setFileSize(1234)
            ->setMimeType('image/webp');

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('customNameExists')
            ->with('Taken name', null)
            ->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->willReturn('Taka niestandardowa nazwa obrazka już istnieje.');

        $controller = new TestMediaController();
        $request = new Request([], [
            '_token' => 'valid',
            'custom_name' => 'Taken name',
        ]);
        $controller->setCsrfValidity(true);

        $response = $controller->rename($mediaImage, $request, $repository, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('Current', $mediaImage->getCustomName());
    }

    public function testRenameHandlesUniqueConstraintViolationDuringFlush(): void
    {
        $mediaImage = (new MediaImage())
            ->setOriginalFilename('hero.webp')
            ->setCustomName('Current')
            ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
            ->setFileSize(1234)
            ->setMimeType('image/webp');

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('customNameExists')
            ->with('Taken name', null)
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new class() extends UniqueConstraintViolationException {
                public function __construct()
                {
                }
            });

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Taka niestandardowa nazwa obrazka już istnieje.', 'This custom image name already exists.')
            ->willReturn('Taka niestandardowa nazwa obrazka już istnieje.');

        $controller = new TestMediaController();
        $request = new Request([], [
            '_token' => 'valid',
            'custom_name' => 'Taken name',
        ]);
        $controller->setCsrfValidity(true);

        $response = $controller->rename($mediaImage, $request, $repository, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame([
            [
                'type' => 'error',
                'message' => 'Taka niestandardowa nazwa obrazka już istnieje.',
            ],
        ], $controller->capturedFlashes);
    }

    public function testGalleryRemovesStoredFileWhenFlushFails(): void
    {
        $repository = $this->createMock(MediaImageRepository::class);
        $repository->expects($this->never())->method('findAllForAdminIndex');

        $storage = $this->createMock(MediaImageStorage::class);
        $storage
            ->expects($this->once())
            ->method('store')
            ->willReturn([
                'relative_path' => 'public/uploads/media/2026/04/05/media-image-1.webp',
                'original_filename' => 'hero.webp',
                'file_size' => 1234,
                'mime_type' => 'image/webp',
            ]);

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager
            ->expects($this->once())
            ->method('delete')
            ->with($this->callback(static function (MediaImage $mediaImage): bool {
                return 'public/uploads/media/2026/04/05/media-image-1.webp' === $mediaImage->getFilePath()
                    && 'hero.webp' === $mediaImage->getOriginalFilename()
                    && 1234 === $mediaImage->getFileSize()
                    && 'image/webp' === $mediaImage->getMimeType();
            }));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('flush failed'));

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $controller = new TestMediaController();
        $sourcePath = sys_get_temp_dir().'/blog-system-ai-media-controller-upload-'.bin2hex(random_bytes(4)).'.webp';
        file_put_contents($sourcePath, base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAUAmJaACdLoB+AADsAD+8ut//NgVzXPv9//S4P0uD9Lg/9KQAAA='));

        $request = new Request([], ['media_image_upload' => []], [], [], [
            'media_image_upload' => [
                'imageFile' => new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $sourcePath,
                    'hero.webp',
                    'image/webp',
                    null,
                    true
                ),
            ],
        ], ['REQUEST_METHOD' => 'POST']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('flush failed');

        try {
            $controller->gallery($request, $repository, $storage, $galleryManager, $entityManager, $userLanguageResolver);
        } finally {
            @unlink($sourcePath);
        }
    }

    public function testDeleteFlushesEntityBeforeRemovingFile(): void
    {
        $mediaImage = (new MediaImage())
            ->setOriginalFilename('hero.webp')
            ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
            ->setFileSize(1234)
            ->setMimeType('image/webp');

        $flushCompleted = false;

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager
            ->expects($this->once())
            ->method('delete')
            ->with($mediaImage)
            ->willReturnCallback(function () use (&$flushCompleted): void {
                self::assertTrue($flushCompleted, 'File deletion should happen after flush succeeds.');
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($mediaImage);
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$flushCompleted): void {
                $flushCompleted = true;
            });

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Obrazek został usunięty z galerii.', 'The image has been removed from the gallery.')
            ->willReturn('Obrazek został usunięty z galerii.');

        $controller = new TestMediaController();
        $controller->setCsrfValidity(true);

        $response = $controller->delete($mediaImage, new Request([], ['_token' => 'valid']), $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame([
            [
                'type' => 'success',
                'message' => 'Obrazek został usunięty z galerii.',
            ],
        ], $controller->capturedFlashes);
    }

    public function testClearFlushesEntitiesBeforeRemovingFiles(): void
    {
        $mediaImages = [
            (new MediaImage())
                ->setOriginalFilename('hero.webp')
                ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
                ->setFileSize(1234)
                ->setMimeType('image/webp'),
            (new MediaImage())
                ->setOriginalFilename('cover.webp')
                ->setFilePath('public/uploads/media/2026/04/05/media-image-2.webp')
                ->setFileSize(5678)
                ->setMimeType('image/webp'),
        ];

        $flushCompleted = false;

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with([])
            ->willReturn($mediaImages);

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager
            ->expects($this->once())
            ->method('clear')
            ->with($mediaImages)
            ->willReturnCallback(function () use (&$flushCompleted): array {
                self::assertTrue($flushCompleted, 'File deletion should happen after flush succeeds.');
                return [];
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(2))
            ->method('remove')
            ->with($this->callback(static fn (MediaImage $mediaImage): bool => in_array($mediaImage, $mediaImages, true)));
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$flushCompleted): void {
                $flushCompleted = true;
            });

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->once())
            ->method('translate')
            ->with('Galeria została wyczyszczona.', 'The gallery has been cleared.')
            ->willReturn('Galeria została wyczyszczona.');

        $controller = new TestMediaController();
        $controller->setCsrfValidity(true);

        $response = $controller->clear(new Request([], ['_token' => 'valid']), $repository, $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame([
            [
                'type' => 'success',
                'message' => 'Galeria została wyczyszczona.',
            ],
        ], $controller->capturedFlashes);
    }

    public function testDeleteDoesNotFailWhenFileDeletionFailsAfterFlush(): void
    {
        $mediaImage = (new MediaImage())
            ->setOriginalFilename('hero.webp')
            ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
            ->setFileSize(1234)
            ->setMimeType('image/webp');

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager
            ->expects($this->once())
            ->method('delete')
            ->with($mediaImage)
            ->willThrowException(new \RuntimeException('unlink failed'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($mediaImage);
        $entityManager->expects($this->once())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->exactly(2))
            ->method('translate')
            ->willReturnMap([
                ['Obrazek został usunięty z galerii.', 'The image has been removed from the gallery.', 'Obrazek został usunięty z galerii.'],
                ['Obrazek został usunięty z galerii, ale nie udało się usunąć pliku z dysku.', 'The image was removed from the gallery, but the file could not be deleted from disk.', 'Obrazek został usunięty z galerii, ale nie udało się usunąć pliku z dysku.'],
            ]);

        $controller = new TestMediaController();
        $controller->setCsrfValidity(true);

        $response = $controller->delete($mediaImage, new Request([], ['_token' => 'valid']), $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame([
            [
                'type' => 'success',
                'message' => 'Obrazek został usunięty z galerii.',
            ],
            [
                'type' => 'error',
                'message' => 'Obrazek został usunięty z galerii, ale nie udało się usunąć pliku z dysku.',
            ],
        ], $controller->capturedFlashes);
    }

    public function testClearDoesNotFailWhenFileDeletionFailsAfterFlush(): void
    {
        $mediaImages = [
            (new MediaImage())
                ->setOriginalFilename('hero.webp')
                ->setFilePath('public/uploads/media/2026/04/05/media-image-1.webp')
                ->setFileSize(1234)
                ->setMimeType('image/webp'),
        ];

        $repository = $this->createMock(MediaImageRepository::class);
        $repository
            ->expects($this->once())
            ->method('findBy')
            ->with([])
            ->willReturn($mediaImages);

        $galleryManager = $this->createMock(MediaGalleryManager::class);
        $galleryManager
            ->expects($this->once())
            ->method('clear')
            ->with($mediaImages)
            ->willReturn(['public/uploads/media/2026/04/05/media-image-1.webp']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($mediaImages[0]);
        $entityManager->expects($this->once())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver
            ->expects($this->exactly(2))
            ->method('translate')
            ->willReturnMap([
                ['Galeria została wyczyszczona.', 'The gallery has been cleared.', 'Galeria została wyczyszczona.'],
                ['Galeria została wyczyszczona, ale nie udało się usunąć wszystkich plików z dysku.', 'The gallery was cleared, but not all files could be deleted from disk.', 'Galeria została wyczyszczona, ale nie udało się usunąć wszystkich plików z dysku.'],
            ]);

        $controller = new TestMediaController();
        $controller->setCsrfValidity(true);

        $response = $controller->clear(new Request([], ['_token' => 'valid']), $repository, $galleryManager, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame([
            [
                'type' => 'success',
                'message' => 'Galeria została wyczyszczona.',
            ],
            [
                'type' => 'error',
                'message' => 'Galeria została wyczyszczona, ale nie udało się usunąć wszystkich plików z dysku.',
            ],
        ], $controller->capturedFlashes);
    }
}

final class TestMediaController extends MediaController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    private bool $csrfValidity = true;

    /** @var list<array{type: string, message: string}> */
    public array $capturedFlashes = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    public function setCsrfValidity(bool $csrfValidity): void
    {
        $this->csrfValidity = $csrfValidity;
    }

    public function addFlash(string $type, mixed $message): void
    {
        $this->capturedFlashes[] = [
            'type' => $type,
            'message' => (string) $message,
        ];
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfValidity;
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/'.$route, $status);
    }

    protected function getUser(): ?UserInterface
    {
        return null;
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create(MediaImageUploadType::class, $data, $options);
    }
}
