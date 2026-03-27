<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\BlogSettingsController;
use App\Entity\BlogSettings;
use App\Repository\BlogSettingsRepository;
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

final class BlogSettingsControllerTest extends TestCase
{
    public function testIndexRendersCurrentSettings(): void
    {
        $settings = (new BlogSettings())->setBlogTitle('Configured blog');

        $repository = $this->createMock(BlogSettingsRepository::class);
        $repository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn($settings);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $controller = new TestBlogSettingsController();
        $response = $controller->index(new Request(), $repository, $entityManager);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/blog_settings/index.html.twig', $controller->capturedView);
        $this->assertSame($settings, $controller->capturedParameters['settings']);
    }

    public function testIndexPersistsNewSettingsOnValidSubmit(): void
    {
        $repository = $this->createMock(BlogSettingsRepository::class);
        $repository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (BlogSettings $settings): bool {
                $this->assertSame('https://example.com', $settings->getAppUrl());
                $this->assertSame('Nowy blog', $settings->getBlogTitle());
                $this->assertSame(8, $settings->getArticlesPerPage());
                $this->assertSame(30, $settings->getAdminArticlesPerPage());

                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $request = new Request([], [
            'blog_settings' => [
                'appUrl' => 'https://example.com/',
                'blogTitle' => 'Nowy blog',
                'homepageSeoDescription' => 'Opis strony glownej dla testu.',
                'homepageSocialImage' => '/assets/img/test-social.jpg',
                'homepageSeoKeywords' => 'php, test, blog',
                'articlesPerPage' => '8',
                'adminArticlesPerPage' => '30',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestBlogSettingsController();
        $response = $controller->index($request, $repository, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/settings/blog', $response->getTargetUrl());
        $this->assertSame([['success', 'Ustawienia bloga zostały zapisane.']], $controller->flashes);
    }

    public function testIndexUpdatesExistingSettingsWithoutPersistingAgain(): void
    {
        $settings = (new BlogSettings())->setBlogTitle('Old title');
        $this->setEntityId($settings, 7);

        $repository = $this->createMock(BlogSettingsRepository::class);
        $repository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn($settings);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $request = new Request([], [
            'blog_settings' => [
                'appUrl' => 'https://www.example.com',
                'blogTitle' => 'Zmieniony blog',
                'homepageSeoDescription' => 'Aktualizacja opisu SEO.',
                'homepageSocialImage' => 'https://cdn.example.com/social.jpg',
                'homepageSeoKeywords' => 'blog, update',
                'articlesPerPage' => '9',
                'adminArticlesPerPage' => '31',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestBlogSettingsController();
        $response = $controller->index($request, $repository, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/settings/blog', $response->getTargetUrl());
        $this->assertSame('Zmieniony blog', $settings->getBlogTitle());
        $this->assertSame(9, $settings->getArticlesPerPage());
        $this->assertSame(31, $settings->getAdminArticlesPerPage());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}

final class TestBlogSettingsController extends BlogSettingsController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    public function addFlash(string $type, mixed $message): void
    {
        $this->flashes[] = [$type, (string) $message];
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse('/admin/settings/blog', $status);
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
            ->create($type, $data, $options);
    }
}
