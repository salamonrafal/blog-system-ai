<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Entity\BlogSettings;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Enum\ArticleImportQueueStatus;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\ArticleRepository;
use App\Repository\BlogSettingsRepository;
use App\Repository\TopMenuItemRepository;
use App\Repository\UserRepository;
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
        $articleCategoryRepository = $this->createCategoryRepositoryMock([
            'all' => 5,
            ArticleCategoryStatus::ACTIVE->value => 4,
            ArticleCategoryStatus::INACTIVE->value => 1,
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
        $topMenuItemRepository = $this->createTopMenuRepositoryMock([
            'all' => 6,
            'active' => 5,
            'inactive' => 1,
        ]);
        $userRepository = $this->createUserRepositoryMock([
            'all' => 4,
            'active' => 3,
            'inactive' => 1,
            'admins' => 2,
        ]);

        $controller = new TestDashboardController();
        $response = $controller->index(
            $articleRepository,
            $articleCategoryRepository,
            $articleImportQueueRepository,
            $articleExportRepository,
            $articleExportQueueRepository,
            $blogSettingsRepository,
            $topMenuItemRepository,
            $userRepository,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('admin/dashboard/index.html.twig', $controller->capturedView);

        $panels = $controller->capturedParameters['dashboard_panels'] ?? null;
        $this->assertIsArray($panels);
        $this->assertCount(8, $panels);

        $this->assertSame('Artykuły', $panels[0]['title']);
        $this->assertSame('admin_dashboard_panel_articles_title', $panels[0]['title_key']);
        $this->assertSame([
            ['value' => 11, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_drafts', 'label' => 'Szkice'],
            ['value' => 3, 'label_key' => 'admin_dashboard_stat_review', 'label' => 'W recenzji'],
            ['value' => 4, 'label_key' => 'admin_dashboard_stat_published', 'label' => 'Opublikowane'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_archived', 'label' => 'Archiwum'],
        ], $panels[0]['stats']);

        $this->assertSame('Kategorie', $panels[1]['title']);
        $this->assertSame('admin_dashboard_panel_categories_title', $panels[1]['title_key']);
        $this->assertSame([
            ['value' => 5, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 4, 'label_key' => 'admin_dashboard_stat_active', 'label' => 'Aktywne'],
            ['value' => 1, 'label_key' => 'admin_dashboard_stat_inactive', 'label' => 'Nieaktywne'],
        ], $panels[1]['stats']);

        $this->assertSame('Importy', $panels[2]['title']);
        $this->assertSame('admin_dashboard_panel_imports_title', $panels[2]['title_key']);
        $this->assertSame([
            ['value' => 8, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_pending', 'label' => 'Oczekujące'],
            ['value' => 1, 'label_key' => 'admin_dashboard_stat_processing', 'label' => 'W trakcie'],
            ['value' => 4, 'label_key' => 'admin_dashboard_stat_completed', 'label' => 'Zakończone'],
            ['value' => 1, 'label_key' => 'admin_dashboard_stat_errors', 'label' => 'Błędy'],
        ], $panels[2]['stats']);

        $this->assertSame('Eksporty', $panels[3]['title']);
        $this->assertSame('admin_dashboard_panel_exports_title', $panels[3]['title_key']);
        $this->assertSame([
            ['value' => 6, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 6, 'label_key' => 'admin_dashboard_stat_articles', 'label' => 'Artykuły'],
            ['value' => 4, 'label_key' => 'admin_dashboard_stat_new', 'label' => 'Nowe'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_downloaded', 'label' => 'Pobrane'],
        ], $panels[3]['stats']);

        $this->assertSame('Stan kolejek', $panels[4]['title']);
        $this->assertSame('admin_dashboard_panel_queue_title', $panels[4]['title_key']);
        $this->assertSame('all', $panels[4]['sections'][0]['key']);
        $this->assertSame('import', $panels[4]['sections'][1]['key']);
        $this->assertSame('export', $panels[4]['sections'][2]['key']);
        $this->assertSame('admin_dashboard_stat_all', $panels[4]['sections'][0]['title_key']);
        $this->assertSame('admin_dashboard_queue_import', $panels[4]['sections'][1]['title_key']);
        $this->assertSame('admin_dashboard_queue_export', $panels[4]['sections'][2]['title_key']);
        $this->assertSame([
            ['value' => 13, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 3, 'label_key' => 'admin_dashboard_stat_pending', 'label' => 'Oczekujące'],
            ['value' => 3, 'label_key' => 'admin_dashboard_stat_processing', 'label' => 'W trakcie'],
            ['value' => 5, 'label_key' => 'admin_dashboard_stat_completed', 'label' => 'Zakończone'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_errors', 'label' => 'Błędy'],
        ], $panels[4]['sections'][0]['stats']);

        $this->assertSame('Użytkownicy', $panels[5]['title']);
        $this->assertSame('admin_dashboard_panel_users_title', $panels[5]['title_key']);
        $this->assertSame([
            ['value' => 4, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 3, 'label_key' => 'admin_dashboard_stat_active', 'label' => 'Aktywne'],
            ['value' => 1, 'label_key' => 'admin_dashboard_stat_inactive', 'label' => 'Nieaktywne'],
            ['value' => 2, 'label_key' => 'admin_dashboard_stat_admins', 'label' => 'Admini'],
        ], $panels[5]['stats']);

        $this->assertSame('Top menu', $panels[6]['title']);
        $this->assertSame('admin_dashboard_panel_top_menu_title', $panels[6]['title_key']);
        $this->assertSame([
            ['value' => 6, 'label_key' => 'admin_dashboard_stat_all', 'label' => 'Wszystkie'],
            ['value' => 5, 'label_key' => 'admin_dashboard_stat_active', 'label' => 'Aktywne'],
            ['value' => 1, 'label_key' => 'admin_dashboard_stat_inactive', 'label' => 'Nieaktywne'],
        ], $panels[6]['stats']);

        $this->assertSame('Ustawienia bloga', $panels[7]['title']);
        $this->assertSame('admin_dashboard_panel_settings_title', $panels[7]['title_key']);
        $this->assertSame([
            ['label_key' => 'admin_dashboard_meta_blog_title', 'label' => 'Tytuł bloga', 'value' => 'AI Ops Blog', 'value_key' => null],
            ['label_key' => 'admin_dashboard_meta_articles_per_page', 'label' => 'Artykułów na stronę', 'value' => '9', 'value_key' => null],
            ['label_key' => 'admin_dashboard_meta_last_update', 'label' => 'Ostatnia aktualizacja', 'value' => $settings->getUpdatedAt()->format('Y-m-d H:i'), 'value_key' => null],
        ], $panels[7]['meta_cards']);
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
        $articleCategoryRepository = $this->createCategoryRepositoryMock([
            'all' => 0,
            ArticleCategoryStatus::ACTIVE->value => 0,
            ArticleCategoryStatus::INACTIVE->value => 0,
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
        $topMenuItemRepository = $this->createTopMenuRepositoryMock([
            'all' => 0,
            'active' => 0,
            'inactive' => 0,
        ]);
        $userRepository = $this->createUserRepositoryMock([
            'all' => 0,
            'active' => 0,
            'inactive' => 0,
            'admins' => 0,
        ]);

        $controller = new TestDashboardController();
        $controller->index(
            $articleRepository,
            $articleCategoryRepository,
            $articleImportQueueRepository,
            $articleExportRepository,
            $articleExportQueueRepository,
            $blogSettingsRepository,
            $topMenuItemRepository,
            $userRepository,
        );

        $panels = $controller->capturedParameters['dashboard_panels'];
        $settingsPanel = $panels[7];

        $this->assertSame([
            ['label_key' => 'admin_dashboard_meta_blog_title', 'label' => 'Tytuł bloga', 'value' => BlogSettings::DEFAULT_BLOG_TITLE, 'value_key' => null],
            ['label_key' => 'admin_dashboard_meta_articles_per_page', 'label' => 'Artykułów na stronę', 'value' => (string) BlogSettings::DEFAULT_ARTICLES_PER_PAGE, 'value_key' => null],
            ['label_key' => 'admin_dashboard_meta_last_update', 'label' => 'Ostatnia aktualizacja', 'value' => 'Brak zapisanych zmian', 'value_key' => 'admin_dashboard_meta_no_saved_changes'],
        ], $settingsPanel['meta_cards']);
    }

    /**
     * @param array<string, int> $counts
     */
    private function createCategoryRepositoryMock(array $counts): ArticleCategoryRepository
    {
        /** @var ArticleCategoryRepository&MockObject $repository */
        $repository = $this->createMock(ArticleCategoryRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                return $counts[$criteria['status']->value] ?? 0;
            });
        $repository
            ->method('countActive')
            ->willReturn($counts[ArticleCategoryStatus::ACTIVE->value] ?? 0);
        $repository
            ->method('countInactive')
            ->willReturn($counts[ArticleCategoryStatus::INACTIVE->value] ?? 0);

        return $repository;
    }

    /**
     * @param array<string, int> $counts
     */
    private function createTopMenuRepositoryMock(array $counts): TopMenuItemRepository
    {
        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository
            ->method('count')
            ->willReturnCallback(static function (array $criteria) use ($counts): int {
                if ([] === $criteria) {
                    return $counts['all'];
                }

                return $counts[$criteria['status']->value] ?? 0;
            });
        $repository->method('countActive')->willReturn($counts['active'] ?? 0);
        $repository->method('countInactive')->willReturn($counts['inactive'] ?? 0);

        return $repository;
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
    private function createUserRepositoryMock(array $counts): UserRepository
    {
        /** @var UserRepository&MockObject $repository */
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->method('count')
            ->willReturn($counts['all']);
        $repository
            ->method('countActive')
            ->willReturn($counts['active']);
        $repository
            ->method('countInactive')
            ->willReturn($counts['inactive']);
        $repository
            ->method('countAdministrators')
            ->willReturn($counts['admins']);

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
