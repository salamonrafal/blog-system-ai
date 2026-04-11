<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleKeywordController;
use App\Entity\ArticleKeyword;
use App\Entity\User;
use App\Enum\ArticleKeywordLanguage;
use App\Repository\ArticleKeywordExportQueueRepository;
use App\Repository\ArticleKeywordRepository;
use App\Service\ArticleKeywordNameGenerator;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validation;

final class ArticleKeywordControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testIndexBuildsExpectedKeywordStatistics(): void
    {
        $keyword = (new ArticleKeyword())->setName('php');

        /** @var ArticleKeywordRepository&MockObject $keywordRepository */
        $keywordRepository = $this->createMock(ArticleKeywordRepository::class);
        $keywordRepository->expects($this->once())->method('findForAdminIndex')->willReturn([$keyword]);
        $keywordRepository->expects($this->once())->method('count')->with([])->willReturn(1);
        $keywordRepository->expects($this->once())->method('countActive')->willReturn(1);
        $keywordRepository->expects($this->once())->method('countInactive')->willReturn(0);

        $controller = new TestArticleKeywordController();
        $response = $controller->index($keywordRepository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_keyword/index.html.twig', $controller->capturedView);
        $this->assertSame(['all' => 1, 'active' => 1, 'inactive' => 0], $controller->capturedParameters['keyword_stats']);
    }

    public function testNewPersistsKeywordOnValidSubmit(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $nameGenerator = $this->createMock(ArticleKeywordNameGenerator::class);
        $nameGenerator
            ->expects($this->once())
            ->method('refreshName')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $keyword->setName('php-8-4');

                return true;
            }));

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $this->assertSame(ArticleKeywordLanguage::EN, $keyword->getLanguage());
                $this->assertSame('php-8-4', $keyword->getName());
                $this->assertNull($keyword->getColor());

                return true;
            }));
        $entityManager->expects($this->once())->method('flush');

        $request = new Request([], [
            'article_keyword' => [
                'language' => 'en',
                'name' => 'PHP 8.4',
                'status' => 'active',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestArticleKeywordController();
        $response = $controller->new(
            $request,
            $entityManager,
            $nameGenerator,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/article-keywords', $response->getTargetUrl());
        $this->assertSame([['success', 'Keyword created.']], $controller->flashes);
    }

    public function testEditUpdatesKeywordOnValidSubmit(): void
    {
        $keyword = (new ArticleKeyword())
            ->setName('php')
            ->setColor('#39ff14');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $nameGenerator = $this->createMock(ArticleKeywordNameGenerator::class);
        $nameGenerator
            ->expects($this->once())
            ->method('refreshName')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $keyword->setName('symfony');

                return true;
            }));

        $request = new Request([], [
            'article_keyword' => [
                'language' => 'pl',
                'name' => 'Symfony',
                'status' => 'inactive',
                'color' => '#ff6600',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestArticleKeywordController();
        $response = $controller->edit(
            $keyword,
            $request,
            $entityManager,
            $nameGenerator,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/article-keywords', $response->getTargetUrl());
        $this->assertSame('symfony', $keyword->getName());
        $this->assertSame('#ff6600', $keyword->getColor());
        $this->assertSame([['success', 'Słowo kluczowe zostało zaktualizowane.']], $controller->flashes);
    }

    public function testNewAllowsSharedKeywordLanguage(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $nameGenerator = $this->createMock(ArticleKeywordNameGenerator::class);
        $nameGenerator
            ->expects($this->once())
            ->method('refreshName')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $keyword->setName('api');

                return true;
            }));

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $this->assertSame(ArticleKeywordLanguage::ALL, $keyword->getLanguage());

                return true;
            }));
        $entityManager->expects($this->once())->method('flush');

        $request = new Request([], [
            'article_keyword' => [
                'language' => 'all',
                'name' => 'API',
                'status' => 'active',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestArticleKeywordController();
        $response = $controller->new(
            $request,
            $entityManager,
            $nameGenerator,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/article-keywords', $response->getTargetUrl());
    }

    public function testNewDoesNotGenerateFallbackNameForBlankSubmit(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $nameGenerator = $this->createMock(ArticleKeywordNameGenerator::class);
        $nameGenerator->expects($this->never())->method('refreshName');

        $request = new Request([], [
            'article_keyword' => [
                'language' => 'pl',
                'name' => '   ',
                'status' => 'active',
                'color' => '',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestArticleKeywordController();
        $response = $controller->new(
            $request,
            $entityManager,
            $nameGenerator,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_keyword/new.html.twig', $controller->capturedView);
        $this->assertInstanceOf(FormInterface::class, $controller->capturedParameters['form']);
        $this->assertTrue($controller->capturedParameters['form']->isSubmitted());
        $this->assertFalse($controller->capturedParameters['form']->isValid());
    }

    public function testEditAllowsClearingKeywordColor(): void
    {
        $keyword = (new ArticleKeyword())
            ->setName('php')
            ->setColor('#39ff14');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $nameGenerator = $this->createMock(ArticleKeywordNameGenerator::class);
        $nameGenerator
            ->expects($this->once())
            ->method('refreshName')
            ->with($this->callback(function (ArticleKeyword $keyword): bool {
                $keyword->setName('php');

                return true;
            }));

        $request = new Request([], [
            'article_keyword' => [
                'language' => 'pl',
                'name' => 'PHP',
                'status' => 'active',
                'color' => '',
            ],
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $controller = new TestArticleKeywordController();
        $response = $controller->edit(
            $keyword,
            $request,
            $entityManager,
            $nameGenerator,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNull($keyword->getColor());
    }

    public function testDeleteRemovesKeywordWhenCsrfTokenIsValid(): void
    {
        $keyword = (new ArticleKeyword())->setName('php');
        $this->setEntityId($keyword, 5);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($keyword);
        $entityManager->expects($this->once())->method('flush');

        $controller = new TestArticleKeywordController();
        $controller->csrfTokenIsValid = true;

        $response = $controller->delete(
            $keyword,
            new Request([], ['_token' => 'valid']),
            $entityManager,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/article-keywords', $response->getTargetUrl());
        $this->assertSame([['success', 'Keyword deleted.']], $controller->flashes);
    }

    public function testDeleteThrowsAccessDeniedWhenCsrfTokenIsInvalid(): void
    {
        $keyword = (new ArticleKeyword())->setName('php');
        $this->setEntityId($keyword, 5);

        $controller = new TestArticleKeywordController();
        $controller->csrfTokenIsValid = false;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $controller->delete(
            $keyword,
            new Request([], ['_token' => 'invalid']),
            $this->createMock(EntityManagerInterface::class),
            $this->createUserLanguageResolverMock('pl'),
        );
    }

    public function testExportAddsKeywordSnapshotToQueueWhenRepositoryEnqueuesIt(): void
    {
        $currentUser = (new User())
            ->setEmail('exporter@example.com')
            ->setFullName('Eksporter');

        $queueRepository = $this->createMock(ArticleKeywordExportQueueRepository::class);
        $queueRepository
            ->expects($this->once())
            ->method('enqueuePending')
            ->with($currentUser)
            ->willReturn(true);

        $controller = new TestArticleKeywordController();
        $controller->authenticatedUser = $currentUser;
        $controller->csrfTokenIsValid = true;

        $response = $controller->export(
            new Request([], ['_token' => 'valid']),
            $queueRepository,
            $this->createUserLanguageResolverMock('pl'),
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/article-keywords', $response->getTargetUrl());
        $this->assertSame([['success', 'Eksport słów kluczowych został dodany do kolejki.']], $controller->flashes);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}

final class TestArticleKeywordController extends ArticleKeywordController
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
        return new RedirectResponse('/admin/article-keywords', $status);
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new class implements ConstraintValidatorFactoryInterface
            {
                private readonly ConstraintValidatorFactory $decorated;

                public function __construct()
                {
                    $this->decorated = new ConstraintValidatorFactory();
                }

                public function getInstance(Constraint $constraint): ConstraintValidator
                {
                    if ($constraint instanceof UniqueEntity) {
                        return new class extends ConstraintValidator
                        {
                            public function validate(mixed $value, Constraint $constraint): void
                            {
                            }
                        };
                    }

                    return $this->decorated->getInstance($constraint);
                }
            })
            ->getValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
