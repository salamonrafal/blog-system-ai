<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Entity\BlogSettings;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Enum\ArticleImportQueueStatus;
use App\Enum\ArticleStatus;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\ArticleRepository;
use App\Repository\BlogSettingsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class DashboardControllerTest extends TestCase
{
    public function testIndexBuildsDashboardPanelsWithExpectedStatsAndSections(): void
    {
        $settings = (new BlogSettings())
            ->setBlogTitle('AI Ops Blog')
            ->setArticlesPerPage(9);

        $articleRepository = $this->createArticleRepositoryMock([
            'all' => 11,
            ArticleStatus::DRAFT->value => 2,
            ArticleStatus::REVIEW->value => 3,
            ArticleStatus::PUBLISHED->value => 4,
            ArticleStatus::ARCHIVED->value => 2,
        ]);
        $articleImportQueueRepository = $this->createImportQueueRepositoryMock([
            'all' => 8,
            ArticleImportQueueStatus::PENDING->value => 2,
            ArticleImportQueueStatus::PROCESSING->value => 1,
            ArticleImportQueueStatus::COMPLETED->value => 4,
            ArticleImportQueueStatus::FAILED->value => 1,
        ]);
        $articleExportRepository = $this->createExportRepositoryMock([
            'all' => 6,
            'type:'.ArticleExportType::ARTICLES->value => 6,
            'status:'.ArticleExportStatus::NEW->value => 4,
            'status:'.ArticleExportStatus::DOWNLOADED->value => 2,
        ]);
        $articleExportQueueRepository = $this->createExportQueueRepositoryMock([
            'all' => 5,
            ArticleExportQueueStatus::PENDING->value => 1,
            ArticleExportQueueStatus::PROCESSING->value => 2,
            ArticleExportQueueStatus::COMPLETED->value => 1,
            ArticleExportQueueStatus::FAILED->value => 1,
        ]);

        $blogSettingsRepository = $this->createMock(BlogSettingsRepository::class);
        $blogSettingsRepository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn($settings);

        $controller = new TestDashboardController();
        $response = $controller->index(
            $articleRepository,
            $articleImportQueueRepository,
            $articleExportRepository,
            $articleExportQueueRepository,
            $blogSettingsRepository,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/dashboard/index.html.twig', $controller->capturedView);

        $panels = $controller->capturedParameters['dashboard_panels'] ?? null;
        $this->assertIsArray($panels);
        $this->assertCount(5, $panels);

        $this->assertSame('Artykuły', $panels[0]['title']);
        $this->assertSame([
            ['value' => 11, 'label' => 'Wszystkie'],
            ['value' => 2, 'label' => 'Szkice'],
            ['value' => 3, 'label' => 'W recenzji'],
            ['value' => 4, 'label' => 'Opublikowane'],
            ['value' => 2, 'label' => 'Archiwum'],
        ], $panels[0]['stats']);

        $this->assertSame('Importy', $panels[1]['title']);
        $this->assertSame([
            ['value' => 8, 'label' => 'Wszystkie'],
            ['value' => 2, 'label' => 'Oczekujące'],
            ['value' => 1, 'label' => 'W trakcie'],
            ['value' => 4, 'label' => 'Zakończone'],
            ['value' => 1, 'label' => 'Błędy'],
        ], $panels[1]['stats']);

        $this->assertSame('Eksporty', $panels[2]['title']);
        $this->assertSame([
            ['value' => 6, 'label' => 'Wszystkie'],
            ['value' => 6, 'label' => 'Artykuły'],
            ['value' => 4, 'label' => 'Nowe'],
            ['value' => 2, 'label' => 'Pobrane'],
        ], $panels[2]['stats']);

        $this->assertSame('Stan kolejek', $panels[3]['title']);
        $this->assertSame('all', $panels[3]['sections'][0]['key']);
        $this->assertSame('import', $panels[3]['sections'][1]['key']);
        $this->assertSame('export', $panels[3]['sections'][2]['key']);
        $this->assertSame([
            ['value' => 13, 'label' => 'Wszystkie'],
            ['value' => 3, 'label' => 'Oczekujące'],
            ['value' => 3, 'label' => 'W trakcie'],
            ['value' => 5, 'label' => 'Zakończone'],
            ['value' => 2, 'label' => 'Błędy'],
        ], $panels[3]['sections'][0]['stats']);

        $this->assertSame('Ustawienia bloga', $panels[4]['title']);
        $this->assertSame([
            ['label' => 'Tytuł bloga', 'value' => 'AI Ops Blog'],
            ['label' => 'Artykułów na stronę', 'value' => '9'],
            ['label' => 'Ostatnia aktualizacja', 'value' => $settings->getUpdatedAt()->format('Y-m-d H:i')],
        ], $panels[4]['meta_cards']);
    }

    public function testIndexUsesDefaultFallbackValuesWhenBlogSettingsAreMissing(): void
    {
        $articleRepository = $this->createArticleRepositoryMock([
            'all' => 0,
            ArticleStatus::DRAFT->value => 0,
            ArticleStatus::REVIEW->value => 0,
            ArticleStatus::PUBLISHED->value => 0,
            ArticleStatus::ARCHIVED->value => 0,
        ]);
        $articleImportQueueRepository = $this->createImportQueueRepositoryMock([
            'all' => 0,
            ArticleImportQueueStatus::PENDING->value => 0,
            ArticleImportQueueStatus::PROCESSING->value => 0,
            ArticleImportQueueStatus::COMPLETED->value => 0,
            ArticleImportQueueStatus::FAILED->value => 0,
        ]);
        $articleExportRepository = $this->createExportRepositoryMock([
            'all' => 0,
            'type:'.ArticleExportType::ARTICLES->value => 0,
            'status:'.ArticleExportStatus::NEW->value => 0,
            'status:'.ArticleExportStatus::DOWNLOADED->value => 0,
        ]);
        $articleExportQueueRepository = $this->createExportQueueRepositoryMock([
            'all' => 0,
            ArticleExportQueueStatus::PENDING->value => 0,
            ArticleExportQueueStatus::PROCESSING->value => 0,
            ArticleExportQueueStatus::COMPLETED->value => 0,
            ArticleExportQueueStatus::FAILED->value => 0,
        ]);

        $blogSettingsRepository = $this->createMock(BlogSettingsRepository::class);
        $blogSettingsRepository
            ->expects($this->once())
            ->method('findCurrent')
            ->willReturn(null);

        $controller = new TestDashboardController();
        $controller->index(
            $articleRepository,
            $articleImportQueueRepository,
            $articleExportRepository,
            $articleExportQueueRepository,
            $blogSettingsRepository,
        );

        $panels = $controller->capturedParameters['dashboard_panels'];
        $settingsPanel = $panels[4];

        $this->assertSame([
            ['label' => 'Tytuł bloga', 'value' => BlogSettings::DEFAULT_BLOG_TITLE],
            ['label' => 'Artykułów na stronę', 'value' => (string) BlogSettings::DEFAULT_ARTICLES_PER_PAGE],
            ['label' => 'Ostatnia aktualizacja', 'value' => 'Brak zapisanych zmian'],
        ], $settingsPanel['meta_cards']);
    }

    /**
     * @param array<string, int> $counts
     */
    private function createArticleRepositoryMock(array $counts): ArticleRepository
    {
        /** @var ArticleRepository&MockObject $repository */
        $repository = $this->createMock(ArticleRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                return $counts[$criteria['status']->value] ?? 0;
            });

        return $repository;
    }

    /**
     * @param array<string, int> $counts
     */
    private function createImportQueueRepositoryMock(array $counts): ArticleImportQueueRepository
    {
        /** @var ArticleImportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleImportQueueRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                return $counts[$criteria['status']->value] ?? 0;
            });

        return $repository;
    }

    /**
     * @param array<string, int> $counts
     */
    private function createExportRepositoryMock(array $counts): ArticleExportRepository
    {
        /** @var ArticleExportRepository&MockObject $repository */
        $repository = $this->createMock(ArticleExportRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                if (isset($criteria['type'])) {
                    return $counts['type:'.$criteria['type']->value] ?? 0;
                }

                return $counts['status:'.$criteria['status']->value] ?? 0;
            });

        return $repository;
    }

    /**
     * @param array<string, int> $counts
     */
    private function createExportQueueRepositoryMock(array $counts): ArticleExportQueueRepository
    {
        /** @var ArticleExportQueueRepository&MockObject $repository */
        $repository = $this->createMock(ArticleExportQueueRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                return $counts[$criteria['status']->value] ?? 0;
            });

        return $repository;
    }
}

final class TestDashboardController extends DashboardController
{
    public string $capturedView = '';

    /**
     * @var array<string, mixed>
     */
    public array $capturedParameters = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->capturedView = $view;
        $this->capturedParameters = $parameters;

        return $response ?? new Response('', Response::HTTP_OK);
    }
}
