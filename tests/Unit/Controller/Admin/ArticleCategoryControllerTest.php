<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleCategoryController;
use App\Entity\ArticleCategory;
use App\Entity\User;
use App\Repository\ArticleCategoryRepository;
use App\Repository\CategoryExportQueueRepository;
use App\Service\CategorySlugger;
use App\Service\UserLanguageResolver;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validation;

final class ArticleCategoryControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsExpectedCategoryStatistics(): void
    {
        $firstCategory = (new ArticleCategory())->setName('PHP');
        $secondCategory = (new ArticleCategory())->setName('AI');

        /** @var ArticleCategoryRepository&MockObject $categoryRepository */
        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository
            ->expects($this->once())
            ->method('findForAdminIndex')
            ->willReturn([$firstCategory, $secondCategory]);
        $categoryRepository
            ->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(2);
        $categoryRepository
            ->expects($this->once())
            ->method('countActive')
            ->willReturn(1);
        $categoryRepository
            ->expects($this->once())
            ->method('countInactive')
            ->willReturn(1);

        $controller = new TestArticleCategoryController();
        $response = $controller->index($categoryRepository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/index.html.twig', $controller->capturedView);
        $this->assertCount(2, $controller->capturedParameters['categories']);
        $this->assertSame([
            'all' => 2,
            'active' => 1,
            'inactive' => 1,
        ], $controller->capturedParameters['category_stats']);
    }

    public function testNewRendersCategoryCreationTemplate(): void
    {
        $controller = new TestArticleCategoryController();
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');
        $response = $controller->new(
            new Request(),
            $this->createMock(EntityManagerInterface::class),
            $userLanguageResolver,
            $this->createMock(CategorySlugger::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/new.html.twig', $controller->capturedView);
    }

    public function testNewPersistsCategoryAndTranslationsOnValidSubmit(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $categorySlugger = $this->createMock(CategorySlugger::class);
        $categorySlugger
            ->expects($this->once())
            ->method('refreshSlug')
            ->with($this->callback(function (ArticleCategory $category): bool {
                $category->setSlug('programowanie-w-php');

                return true;
            }));

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ArticleCategory $category): bool {
                $this->assertSame('PHP', $category->getName());
                $this->assertSame('programowanie-w-php', $category->getSlug());
                $this->assertSame('Backend i architektura aplikacji.', $category->getShortDescription());
                $this->assertSame('Programowanie w PHP', $category->getTitle('pl'));
                $this->assertSame('PHP Development', $category->getTitle('en'));
                $this->assertSame('Opis kategorii po polsku.', $category->getDescription('pl'));
                $this->assertSame('Category description in English.', $category->getDescription('en'));
                $this->assertSame('ph ph-code', $category->getIcon());

                return true;
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $controller = new TestArticleCategoryController();
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $request = new Request([], [
            'article_category' => [
                'name' => 'PHP',
                'shortDescription' => 'Backend i architektura aplikacji.',
                'icon' => 'ph ph-code',
                'status' => 'active',
                'titles' => [
                    'pl' => 'Programowanie w PHP',
                    'en' => 'PHP Development',
                ],
                'descriptions' => [
                    'pl' => 'Opis kategorii po polsku.',
                    'en' => 'Category description in English.',
                ],
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->new($request, $entityManager, $userLanguageResolver, $categorySlugger);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame([['success', 'Category created.']], $controller->flashes);
    }

    public function testNewRerendersTemplateWhenTranslationsAreInvalid(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $controller = new TestArticleCategoryController();
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');
        $categorySlugger = $this->createMock(CategorySlugger::class);
        $categorySlugger->expects($this->once())->method('refreshSlug');

        $request = new Request([], [
            'article_category' => [
                'name' => 'PHP',
                'shortDescription' => 'Backend i architektura aplikacji.',
                'icon' => 'ph ph-code',
                'status' => 'active',
                'titles' => [
                    'pl' => '',
                    'en' => 'PHP Development',
                ],
                'descriptions' => [
                    'pl' => 'Opis kategorii po polsku.',
                    'en' => 'Category description in English.',
                ],
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->new($request, $entityManager, $userLanguageResolver, $categorySlugger);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/new.html.twig', $controller->capturedView);
        $this->assertSame([], $controller->flashes);
    }

    public function testEditUpdatesTranslationsOnValidSubmit(): void
    {
        $category = (new ArticleCategory())
            ->setName('PHP')
            ->setSlug('stary-slug')
            ->setTitle('pl', 'Stary tytuł')
            ->setTitle('en', 'Old title')
            ->setDescription('pl', 'Stary opis')
            ->setDescription('en', 'Old description');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $categorySlugger = $this->createMock(CategorySlugger::class);
        $categorySlugger->expects($this->never())->method('refreshSlug');

        $controller = new TestArticleCategoryController();
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $request = new Request([], [
            'article_category' => [
                'name' => 'PHP',
                'shortDescription' => 'Zaktualizowany opis pomocniczy.',
                'icon' => '/assets/img/php.svg',
                'status' => 'inactive',
                'titles' => [
                    'pl' => 'Nowy tytuł PL',
                    'en' => 'New title EN',
                ],
                'descriptions' => [
                    'pl' => 'Nowy opis PL.',
                    'en' => 'New description EN.',
                ],
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $response = $controller->edit($category, $request, $entityManager, $userLanguageResolver, $categorySlugger);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame('Nowy tytuł PL', $category->getTitle('pl'));
        $this->assertSame('stary-slug', $category->getSlug());
        $this->assertSame('New title EN', $category->getTitle('en'));
        $this->assertSame('Nowy opis PL.', $category->getDescription('pl'));
        $this->assertSame('New description EN.', $category->getDescription('en'));
        $this->assertSame('Zaktualizowany opis pomocniczy.', $category->getShortDescription());
        $this->assertSame('/assets/img/php.svg', $category->getIcon());
        $this->assertSame('inactive', $category->getStatus()->value);
        $this->assertSame([['success', 'Category updated.']], $controller->flashes);
    }

    public function testDeleteRemovesCategoryWhenCsrfTokenIsValid(): void
    {
        $category = (new ArticleCategory())->setName('PHP');
        $this->setEntityId($category, 12);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($category);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $controller = new TestArticleCategoryController();
        $controller->csrfTokenIsValid = true;
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $request = new Request([], [
            '_token' => 'valid-token',
        ]);

        $response = $controller->delete($category, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame([['success', 'Category deleted.']], $controller->flashes);
    }

    public function testDeleteThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $category = (new ArticleCategory())->setName('PHP');
        $this->setEntityId($category, 12);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('remove');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $controller = new TestArticleCategoryController();
        $controller->csrfTokenIsValid = false;
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $request = new Request([], [
            '_token' => 'invalid-token',
        ]);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->delete($category, $request, $entityManager, $userLanguageResolver);
    }

    public function testExportAddsCategoryToQueueWhenRepositoryEnqueuesIt(): void
    {
        $category = (new ArticleCategory())->setName('PHP');
        $this->setEntityId($category, 14);
        $currentUser = (new User())
            ->setEmail('exporter@example.com')
            ->setFullName('Eksporter');

        $queueRepository = $this->createMock(CategoryExportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('enqueuePending')
            ->with($category, $currentUser)
            ->willReturn(true);
        $userLanguageResolver = $this->createUserLanguageResolverMock('pl');

        $controller = new TestArticleCategoryController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $response = $controller->export($category, new Request([], ['_token' => 'valid']), $queueRepository, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame([['success', 'Eksport kategorii został dodany do kolejki.']], $controller->flashes);
    }

    public function testExportSelectedRequiresAtLeastOneCategory(): void
    {
        $categoryRepository = $this->createMock(ArticleCategoryRepository::class);
        $categoryRepository->expects($this->never())->method('findBy');

        $queueRepository = $this->createMock(CategoryExportQueueRepository::class);
        $queueRepository->expects($this->never())->method('enqueuePending');
        $userLanguageResolver = $this->createUserLanguageResolverMock('en');

        $controller = new TestArticleCategoryController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->exportSelected(
            new Request([], ['category_ids' => [], '_token' => 'valid']),
            $categoryRepository,
            $queueRepository,
            $userLanguageResolver,
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame([['error', 'Select at least one category to export.']], $controller->flashes);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

}

final class TestArticleCategoryController extends ArticleCategoryController
{
    public ?User $authenticatedUser = null;

    public bool $csrfTokenIsValid = true;

    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    /** @var list<array{0: string, 1: string}> */
    public array $flashes = [];

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return $this->csrfTokenIsValid;
    }

    public function getUser(): ?User
    {
        return $this->authenticatedUser;
    }

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
        return new RedirectResponse('/admin/categories', $status);
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $validator = Validation::createValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
