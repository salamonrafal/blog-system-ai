<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleCategoryController;
use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use App\Service\UserLanguageResolver;
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
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/new.html.twig', $controller->capturedView);
    }

    public function testNewPersistsCategoryAndTranslationsOnValidSubmit(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ArticleCategory $category): bool {
                $this->assertSame('PHP', $category->getName());
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

        $response = $controller->new($request, $entityManager, $userLanguageResolver);

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

        $response = $controller->new($request, $entityManager, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/new.html.twig', $controller->capturedView);
        $this->assertSame([], $controller->flashes);
    }

    public function testEditUpdatesTranslationsOnValidSubmit(): void
    {
        $category = (new ArticleCategory())
            ->setName('PHP')
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

        $response = $controller->edit($category, $request, $entityManager, $userLanguageResolver);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/categories', $response->getTargetUrl());
        $this->assertSame('Nowy tytuł PL', $category->getTitle('pl'));
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

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }

    private function createUserLanguageResolverMock(string $language): UserLanguageResolver
    {
        $resolver = $this->createMock(UserLanguageResolver::class);
        $resolver
            ->method('translate')
            ->willReturnCallback(static fn (string $polish, string $english): string => 'pl' === $language ? $polish : $english);

        return $resolver;
    }
}

final class TestArticleCategoryController extends ArticleCategoryController
{
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
