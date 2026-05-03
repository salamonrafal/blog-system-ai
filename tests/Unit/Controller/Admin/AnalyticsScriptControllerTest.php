<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AnalyticsScriptController;
use App\Entity\AnalyticsScript;
use App\Form\AnalyticsScriptType;
use App\Repository\AnalyticsScriptRepository;
use App\Tests\Unit\Support\MocksUserLanguageResolver;
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
use Symfony\Component\Validator\Validation;

final class AnalyticsScriptControllerTest extends TestCase
{
    use MocksUserLanguageResolver;

    public function testNewDoesNotCheckDuplicatePageNameWhenSubmittedFormIsInvalid(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->never())
            ->method('findOneBy');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $controller = new TestAnalyticsScriptController();

        $response = $controller->new(
            $this->createSubmittedRequest(['pageName' => 'Invalid identifier!']),
            $entityManager,
            $repository,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/analytics_script/new.html.twig', $controller->capturedView);
    }

    public function testNewRerendersFormWhenPageNameAlreadyExistsBeforeFlush(): void
    {
        $existingScript = new AnalyticsScript();
        $this->setEntityId($existingScript, 42);

        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['pageName' => 'google_analytics'])
            ->willReturn($existingScript);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $controller = new TestAnalyticsScriptController();

        $response = $controller->new(
            $this->createSubmittedRequest(),
            $entityManager,
            $repository,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/analytics_script/new.html.twig', $controller->capturedView);
        $this->assertSame(
            'This script identifier is already used.',
            (string) $controller->capturedParameters['form']->get('pageName')->getErrors()[0]->getMessage(),
        );
    }

    public function testNewShowsFormErrorWhenPageNameBecomesDuplicateDuringFlush(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['pageName' => 'google_analytics'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AnalyticsScript::class));
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new class() extends UniqueConstraintViolationException {
                public function __construct()
                {
                }
            });

        $controller = new TestAnalyticsScriptController();

        $response = $controller->new(
            $this->createSubmittedRequest(),
            $entityManager,
            $repository,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/analytics_script/new.html.twig', $controller->capturedView);
        $this->assertSame(
            'This script identifier is already used.',
            (string) $controller->capturedParameters['form']->get('pageName')->getErrors()[0]->getMessage(),
        );
    }

    public function testEditShowsFormErrorWhenPageNameBecomesDuplicateDuringFlush(): void
    {
        $script = new AnalyticsScript();
        $this->setEntityId($script, 12);

        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['pageName' => 'google_analytics'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new class() extends UniqueConstraintViolationException {
                public function __construct()
                {
                }
            });

        $controller = new TestAnalyticsScriptController();

        $response = $controller->edit(
            $script,
            $this->createSubmittedRequest(),
            $entityManager,
            $repository,
            $this->createUserLanguageResolverMock('en'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/analytics_script/edit.html.twig', $controller->capturedView);
        $this->assertSame($script, $controller->capturedParameters['script']);
        $this->assertSame(
            'This script identifier is already used.',
            (string) $controller->capturedParameters['form']->get('pageName')->getErrors()[0]->getMessage(),
        );
    }

    /**
     * @param array<string, string> $overrides
     */
    private function createSubmittedRequest(array $overrides = []): Request
    {
        return new Request([], [
            'analytics_script' => array_merge([
                'pageName' => 'google_analytics',
                'name' => 'Google Analytics',
                'scope' => 'all_public',
                'placement' => 'head',
                'script' => '<script>window.analytics = true;</script>',
                'position' => '0',
                'enabled' => '1',
            ], $overrides),
        ], [], [], [], ['REQUEST_METHOD' => 'POST']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}

final class TestAnalyticsScriptController extends AnalyticsScriptController
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
        return new RedirectResponse('/admin/settings/analytics-scripts', $status);
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
            ->create(AnalyticsScriptType::class, $data, $options);
    }
}
