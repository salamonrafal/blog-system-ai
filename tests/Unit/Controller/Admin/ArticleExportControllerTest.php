<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleExportController;
use App\Entity\ArticleExport;
use App\Repository\ArticleExportRepository;
use App\Service\ArticleExportFileWriter;
use App\Service\ManagedFilePathResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ArticleExportControllerTest extends TestCase
{
    public function testIndexUsesDedicatedRepositoryMethodForAdminList(): void
    {
        $exports = [
            (new ArticleExport())->setFilePath('var/exports/first.json'),
            (new ArticleExport())->setFilePath('var/exports/second.json'),
        ];

        $repository = $this->createMock(ArticleExportRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAllForAdminIndex')
            ->willReturn($exports);
        $repository
            ->expects($this->never())
            ->method('findBy');

        $controller = new TestArticleExportController(
            $this->createStub(ManagedFilePathResolver::class),
            $this->createStub(ArticleExportFileWriter::class),
        );
        $response = $controller->index($repository);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_export/index.html.twig', $controller->capturedView);
        $this->assertSame($exports, $controller->capturedParameters['exports']);
    }
}

final class TestArticleExportController extends ArticleExportController
{
    public string $capturedView = '';

    /** @var array<string, mixed> */
    public array $capturedParameters = [];

    public function __construct(
        ManagedFilePathResolver $managedFilePathResolver,
        ArticleExportFileWriter $articleExportFileWriter,
    )
    {
        parent::__construct($managedFilePathResolver, $articleExportFileWriter);
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return new Response('', Response::HTTP_OK);
    }
}
