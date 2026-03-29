<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleImportController;
use App\Entity\ArticleImportQueue;
use App\Form\ArticleImportType;
use App\Repository\ArticleImportQueueRepository;
use App\Service\ArticleImportStorage;
use App\Service\ManagedFileDeleter;
use App\Service\ManagedFilePathResolver;
use App\Service\UserLanguageResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class ArticleImportControllerTest extends TestCase
{
    public function testIndexUsesDedicatedRepositoryMethodForAdminList(): void
    {
        $imports = [
            (new ArticleImportQueue())
                ->setOriginalFilename('first.json')
                ->setFilePath('var/imports/first.json'),
            (new ArticleImportQueue())
                ->setOriginalFilename('second.json')
                ->setFilePath('var/imports/second.json'),
        ];

        $repository = $this->createMock(ArticleImportQueueRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAllForAdminIndex')
            ->willReturn($imports);
        $repository
            ->expects($this->never())
            ->method('findBy');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $storage = $this->createMock(ArticleImportStorage::class);
        $storage->expects($this->never())->method('store');

        $controller = new TestArticleImportController(
            $this->createStub(ManagedFilePathResolver::class),
            $this->createStub(ManagedFileDeleter::class),
        );

        $userLanguageResolver = $this->createMock(UserLanguageResolver::class);
        $userLanguageResolver->expects($this->never())->method('translate');

        $response = $controller->index(new Request(), $entityManager, $repository, $storage, $userLanguageResolver);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_import/index.html.twig', $controller->capturedView);
        $this->assertSame($imports, $controller->capturedParameters['imports']);
    }
}

final class TestArticleImportController extends ArticleImportController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
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
            ->create(ArticleImportType::class, $data, $options);
    }
}
