<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\BlogSettings;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleImportQueueStatus;
use App\Enum\ArticleStatus;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleImportQueueRepository;
use App\Repository\ArticleRepository;
use App\Repository\BlogSettingsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        ArticleRepository $articleRepository,
        ArticleCategoryRepository $articleCategoryRepository,
        ArticleImportQueueRepository $articleImportQueueRepository,
        ArticleExportRepository $articleExportRepository,
        ArticleExportQueueRepository $articleExportQueueRepository,
        BlogSettingsRepository $blogSettingsRepository,
        UserRepository $userRepository,
    ): Response
    {
        $settings = $blogSettingsRepository->findCurrent();
        $user = $this->resolveDashboardUser();

        return $this->render('admin/dashboard/index.html.twig', [
            'dashboard_user' => [
                'email' => $user instanceof UserInterface ? $user->getUserIdentifier() : 'Nieznany użytkownik',
                'email_label_key' => 'admin_dashboard_user_label',
                'email_label' => 'Użytkownik',
                'role' => $this->resolveRoleLabel($user),
                'role_label_key' => 'admin_dashboard_role_label',
                'role_label' => 'Rola',
                'role_key' => $this->resolveRoleTranslationKey($user),
            ],
            'dashboard_panels' => [
                [
                    'label' => 'admin://articles',
                    'title_key' => 'admin_dashboard_panel_articles_title',
                    'title' => 'Artykuły',
                    'description_key' => 'admin_dashboard_panel_articles_description',
                    'description' => 'Tworzenie, edycja i publikacja wpisów w jednym miejscu.',
                    'stats' => [
                        $this->dashboardStat($articleRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                        $this->dashboardStat($articleRepository->count(['status' => ArticleStatus::DRAFT]), 'admin_dashboard_stat_drafts', 'Szkice'),
                        $this->dashboardStat($articleRepository->count(['status' => ArticleStatus::REVIEW]), 'admin_dashboard_stat_review', 'W recenzji'),
                        $this->dashboardStat($articleRepository->count(['status' => ArticleStatus::PUBLISHED]), 'admin_dashboard_stat_published', 'Opublikowane'),
                        $this->dashboardStat($articleRepository->count(['status' => ArticleStatus::ARCHIVED]), 'admin_dashboard_stat_archived', 'Archiwum'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_article_index'),
                    'secondary_action' => $this->dashboardAction('admin_dashboard_action_add', 'Dodaj', 'admin_article_new'),
                ],
                [
                    'label' => 'admin://categories',
                    'title_key' => 'admin_dashboard_panel_categories_title',
                    'title' => 'Kategorie',
                    'description_key' => 'admin_dashboard_panel_categories_description',
                    'description' => 'Słownik kategorii artykułów z opisami, ikonami i kontrolą aktywności.',
                    'stats' => [
                        $this->dashboardStat($articleCategoryRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                        $this->dashboardStat($articleCategoryRepository->countActive(), 'admin_dashboard_stat_active', 'Aktywne'),
                        $this->dashboardStat($articleCategoryRepository->countInactive(), 'admin_dashboard_stat_inactive', 'Nieaktywne'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_article_category_index'),
                    'secondary_action' => $this->dashboardAction('admin_dashboard_action_add', 'Dodaj', 'admin_article_category_new'),
                ],
                [
                    'label' => 'admin://imports',
                    'title_key' => 'admin_dashboard_panel_imports_title',
                    'title' => 'Importy',
                    'description_key' => 'admin_dashboard_panel_imports_description',
                    'description' => 'Dodawanie paczek JSON do kolejki i kontrola stanu przetwarzania.',
                    'stats' => [
                        $this->dashboardStat($articleImportQueueRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                        $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]), 'admin_dashboard_stat_pending', 'Oczekujące'),
                        $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]), 'admin_dashboard_stat_processing', 'W trakcie'),
                        $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]), 'admin_dashboard_stat_completed', 'Zakończone'),
                        $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]), 'admin_dashboard_stat_errors', 'Błędy'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_article_import_index'),
                    'secondary_action' => $this->dashboardAction('admin_dashboard_action_queues', 'Kolejki', 'admin_queue_status'),
                ],
                [
                    'label' => 'admin://exports',
                    'title_key' => 'admin_dashboard_panel_exports_title',
                    'title' => 'Eksporty',
                    'description_key' => 'admin_dashboard_panel_exports_description',
                    'description' => 'Gotowe pliki eksportu artykułów dostępne do pobrania i ponownego importu.',
                    'stats' => [
                        $this->dashboardStat($articleExportRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                        $this->dashboardStat($articleExportRepository->count(['type' => ArticleExportType::ARTICLES]), 'admin_dashboard_stat_articles', 'Artykuły'),
                        $this->dashboardStat($articleExportRepository->count(['status' => ArticleExportStatus::NEW]), 'admin_dashboard_stat_new', 'Nowe'),
                        $this->dashboardStat($articleExportRepository->count(['status' => ArticleExportStatus::DOWNLOADED]), 'admin_dashboard_stat_downloaded', 'Pobrane'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_article_export_index'),
                ],
                [
                    'label' => 'admin://queue-status',
                    'title_key' => 'admin_dashboard_panel_queue_title',
                    'title' => 'Stan kolejek',
                    'description_key' => 'admin_dashboard_panel_queue_description',
                    'description' => 'Podgląd osobnych kolejek dla importów i eksportów wykonywanych w tle.',
                    'sections' => [
                        [
                            'key' => 'all',
                            'title_key' => 'admin_dashboard_stat_all',
                            'title' => 'Wszystkie',
                            'stats' => [
                                $this->dashboardStat($articleImportQueueRepository->count([]) + $articleExportQueueRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PENDING]), 'admin_dashboard_stat_pending', 'Oczekujące'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PROCESSING]), 'admin_dashboard_stat_processing', 'W trakcie'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::COMPLETED]), 'admin_dashboard_stat_completed', 'Zakończone'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::FAILED]), 'admin_dashboard_stat_errors', 'Błędy'),
                            ],
                        ],
                        [
                            'key' => 'import',
                            'title_key' => 'admin_dashboard_queue_import',
                            'title' => 'Kolejka importu',
                            'stats' => [
                                $this->dashboardStat($articleImportQueueRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]), 'admin_dashboard_stat_pending', 'Oczekujące'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]), 'admin_dashboard_stat_processing', 'W trakcie'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]), 'admin_dashboard_stat_completed', 'Zakończone'),
                                $this->dashboardStat($articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]), 'admin_dashboard_stat_errors', 'Błędy'),
                            ],
                        ],
                        [
                            'key' => 'export',
                            'title_key' => 'admin_dashboard_queue_export',
                            'title' => 'Kolejka eksportu',
                            'stats' => [
                                $this->dashboardStat($articleExportQueueRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                                $this->dashboardStat($articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PENDING]), 'admin_dashboard_stat_pending', 'Oczekujące'),
                                $this->dashboardStat($articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PROCESSING]), 'admin_dashboard_stat_processing', 'W trakcie'),
                                $this->dashboardStat($articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::COMPLETED]), 'admin_dashboard_stat_completed', 'Zakończone'),
                                $this->dashboardStat($articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::FAILED]), 'admin_dashboard_stat_errors', 'Błędy'),
                            ],
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_queue_status'),
                ],
                [
                    'label' => 'admin://users',
                    'title_key' => 'admin_dashboard_panel_users_title',
                    'title' => 'Użytkownicy',
                    'description_key' => 'admin_dashboard_panel_users_description',
                    'description' => 'Zarządzanie dostępem do panelu, aktywnością kont i podstawowymi danymi logowania.',
                    'stats' => [
                        $this->dashboardStat($userRepository->count([]), 'admin_dashboard_stat_all', 'Wszystkie'),
                        $this->dashboardStat($userRepository->countActive(), 'admin_dashboard_stat_active', 'Aktywne'),
                        $this->dashboardStat($userRepository->countInactive(), 'admin_dashboard_stat_inactive', 'Nieaktywne'),
                        $this->dashboardStat($userRepository->countAdministrators(), 'admin_dashboard_stat_admins', 'Admini'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_browse', 'Przeglądaj', 'admin_user_index'),
                    'secondary_action' => $this->dashboardAction('admin_dashboard_action_add', 'Dodaj', 'admin_user_new'),
                ],
                [
                    'label' => 'admin://blog-settings',
                    'title_key' => 'admin_dashboard_panel_settings_title',
                    'title' => 'Ustawienia bloga',
                    'description_key' => 'admin_dashboard_panel_settings_description',
                    'description' => 'Konfiguracja nazwy bloga, SEO strony głównej i liczby wpisów na stronę.',
                    'meta_cards' => [
                        $this->dashboardMetaCard('admin_dashboard_meta_blog_title', 'Tytuł bloga', null !== $settings ? $settings->getBlogTitle() : BlogSettings::DEFAULT_BLOG_TITLE),
                        $this->dashboardMetaCard('admin_dashboard_meta_articles_per_page', 'Artykułów na stronę', null !== $settings ? (string) $settings->getArticlesPerPage() : (string) BlogSettings::DEFAULT_ARTICLES_PER_PAGE),
                        $this->dashboardMetaCard('admin_dashboard_meta_last_update', 'Ostatnia aktualizacja', null !== $settings
                            ? $settings->getUpdatedAt()->format('Y-m-d H:i')
                            : 'Brak zapisanych zmian'),
                    ],
                    'meta' => [],
                    'primary_action' => $this->dashboardAction('admin_dashboard_action_edit', 'Edytuj', 'admin_blog_settings'),
                ],
            ],
        ]);
    }

    private function resolveRoleLabel(?UserInterface $user): string
    {
        if (!$user instanceof UserInterface) {
            return 'Brak roli';
        }

        $roles = $user->getRoles();

        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return 'Administrator';
        }

        if (\in_array('ROLE_USER', $roles, true)) {
            return 'Użytkownik';
        }

        return 'Rola niestandardowa';
    }

    private function resolveRoleTranslationKey(?UserInterface $user): string
    {
        if (!$user instanceof UserInterface) {
            return 'admin_dashboard_role_none';
        }

        $roles = $user->getRoles();

        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin_dashboard_role_admin';
        }

        if (\in_array('ROLE_USER', $roles, true)) {
            return 'admin_dashboard_role_user';
        }

        return 'admin_dashboard_role_custom';
    }

    private function dashboardStat(int $value, string $labelKey, string $label): array
    {
        return [
            'value' => $value,
            'label_key' => $labelKey,
            'label' => $label,
        ];
    }

    private function dashboardAction(string $labelKey, string $label, string $route): array
    {
        return [
            'label_key' => $labelKey,
            'label' => $label,
            'route' => $route,
        ];
    }

    private function dashboardMetaCard(string $labelKey, string $label, string $value): array
    {
        return [
            'label_key' => $labelKey,
            'label' => $label,
            'value' => $value,
        ];
    }

    private function resolveDashboardUser(): ?UserInterface
    {
        if (!isset($this->container)) {
            return null;
        }

        $user = $this->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
