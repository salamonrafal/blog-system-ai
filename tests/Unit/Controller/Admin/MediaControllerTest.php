<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\MediaController;
use App\Entity\MediaImage;
use App\Form\MediaImageUploadType;
use App\Repository\MediaImageRepository;
use App\Service\MediaImageStorage;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class MediaControllerTest extends TestCase
{
    public function testIndexRendersGalleryFromManager(): void
    {
        $storage = $this->createMock(MediaImageStorage::class);
        $storage->expects($this->never())->method('store');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $controller = new TestMediaController();

        $response = $controller->index(new Request(), $storage, $entityManager, $userLanguageResolver);

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

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $response = $controller->gallery(new Request(), $repository, $storage, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/media/gallery.html.twig', $controller->capturedView);
        $this->assertSame($galleryImages, $controller->capturedParameters['gallery_images']);
        $this->assertInstanceOf(FormInterface::class, $controller->capturedParameters['upload_form']);
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
