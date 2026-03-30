<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ArticleExportController;
use App\Entity\ArticleExport;
use App\Enum\ArticleExportType;
use App\Repository\ArticleExportRepository;
use App\Service\ArticleExportFileWriter;
use App\Service\ManagedFilePathResolver;
use App\Service\PaginationBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
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
            ->method('countForAdminIndex')
            ->with(null)
            ->willReturn(2);
        $repository
            ->expects($this->once())
            ->method('findPaginatedForAdminIndex')
            ->with(1, 25, null)
            ->willReturn($exports);
        $repository
            ->expects($this->never())
            ->method('findBy');

        $controller = new TestArticleExportController(
            $this->createStub(ManagedFilePathResolver::class),
            $this->createStub(ArticleExportFileWriter::class),
        );
        $response = $controller->index(new Request(), $repository, new PaginationBuilder());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/article_export/index.html.twig', $controller->capturedView);
        $this->assertSame($exports, $controller->capturedParameters['exports']);
        $this->assertNull($controller->capturedParameters['selected_type']);
        $this->assertSame(ArticleExportType::cases(), $controller->capturedParameters['export_types']);
        $this->assertSame(1, $controller->capturedParameters['current_page']);
        $this->assertSame(1, $controller->capturedParameters['total_pages']);
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
