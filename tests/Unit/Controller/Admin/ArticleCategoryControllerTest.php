<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleCategoryController;
use App\Entity\ArticleCategory;
use App\Repository\ArticleCategoryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Response;
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
        $response = $controller->new(
            new \Symfony\Component\HttpFoundation\Request(),
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_category/new.html.twig', $controller->capturedView);
    }
}

final class TestArticleCategoryController extends ArticleCategoryController
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
        $validator = Validation::createValidator();

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create($type, $data, $options);
    }
}
