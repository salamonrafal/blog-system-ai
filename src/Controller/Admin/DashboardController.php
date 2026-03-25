<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use App\Enum\ArticleExportQueueStatus;
use App\Enum\ArticleImportQueueStatus;
use App\Enum\ArticleStatus;
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
                'role' => $this->resolveRoleLabel($user),
            ],
            'dashboard_panels' => [
                [
                    'label' => 'admin://articles',
                    'title' => 'Artykuły',
                    'description' => 'Tworzenie, edycja i publikacja wpisów w jednym miejscu.',
                    'stats' => [
                        [
                            'value' => $articleRepository->count([]),
                            'label' => 'Wszystkie',
                        ],
                        [
                            'value' => $articleRepository->count(['status' => ArticleStatus::DRAFT]),
                            'label' => 'Szkice',
                        ],
                        [
                            'value' => $articleRepository->count(['status' => ArticleStatus::REVIEW]),
                            'label' => 'W recenzji',
                        ],
                        [
                            'value' => $articleRepository->count(['status' => ArticleStatus::PUBLISHED]),
                            'label' => 'Opublikowane',
                        ],
                        [
                            'value' => $articleRepository->count(['status' => ArticleStatus::ARCHIVED]),
                            'label' => 'Archiwum',
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Przeglądaj',
                        'route' => 'admin_article_index',
                    ],
                    'secondary_action' => [
                        'label' => 'Nowy artykuł',
                        'route' => 'admin_article_new',
                    ],
                ],
                [
                    'label' => 'admin://imports',
                    'title' => 'Importy',
                    'description' => 'Dodawanie paczek JSON do kolejki i kontrola stanu przetwarzania.',
                    'stats' => [
                        [
                            'value' => $articleImportQueueRepository->count([]),
                            'label' => 'Wszystkie',
                        ],
                        [
                            'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]),
                            'label' => 'Oczekujące',
                        ],
                        [
                            'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]),
                            'label' => 'W trakcie',
                        ],
                        [
                            'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]),
                            'label' => 'Zakończone',
                        ],
                        [
                            'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]),
                            'label' => 'Błędy',
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Otwórz importy',
                        'route' => 'admin_article_import_index',
                    ],
                    'secondary_action' => [
                        'label' => 'Stan kolejek',
                        'route' => 'admin_queue_status',
                    ],
                ],
                [
                    'label' => 'admin://exports',
                    'title' => 'Eksporty',
                    'description' => 'Gotowe pliki eksportu artykułów dostępne do pobrania i ponownego importu.',
                    'stats' => [
                        [
                            'value' => $articleExportRepository->count([]),
                            'label' => 'Wszystkie',
                        ],
                        [
                            'value' => $articleExportRepository->count(['type' => ArticleExportType::ARTICLES]),
                            'label' => 'Artykuły',
                        ],
                        [
                            'value' => $articleExportRepository->count(['status' => ArticleExportStatus::NEW]),
                            'label' => 'Nowe',
                        ],
                        [
                            'value' => $articleExportRepository->count(['status' => ArticleExportStatus::DOWNLOADED]),
                            'label' => 'Pobrane',
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Otwórz eksporty',
                        'route' => 'admin_article_export_index',
                    ],
                ],
                [
                    'label' => 'admin://queue-status',
                    'title' => 'Stan kolejek',
                    'description' => 'Podgląd osobnych kolejek dla importów i eksportów wykonywanych w tle.',
                    'sections' => [
                        [
                            'key' => 'all',
                            'title' => 'Wszystkie',
                            'stats' => [
                                [
                                    'value' => $articleImportQueueRepository->count([]) + $articleExportQueueRepository->count([]),
                                    'label' => 'Wszystkie',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PENDING]),
                                    'label' => 'Oczekujące',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PROCESSING]),
                                    'label' => 'W trakcie',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::COMPLETED]),
                                    'label' => 'Zakończone',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]) + $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::FAILED]),
                                    'label' => 'Błędy',
                                ],
                            ],
                        ],
                        [
                            'key' => 'import',
                            'title' => 'Kolejka importu',
                            'stats' => [
                                [
                                    'value' => $articleImportQueueRepository->count([]),
                                    'label' => 'Wszystkie',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PENDING]),
                                    'label' => 'Oczekujące',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::PROCESSING]),
                                    'label' => 'W trakcie',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::COMPLETED]),
                                    'label' => 'Zakończone',
                                ],
                                [
                                    'value' => $articleImportQueueRepository->count(['status' => ArticleImportQueueStatus::FAILED]),
                                    'label' => 'Błędy',
                                ],
                            ],
                        ],
                        [
                            'key' => 'export',
                            'title' => 'Kolejka eksportu',
                            'stats' => [
                                [
                                    'value' => $articleExportQueueRepository->count([]),
                                    'label' => 'Wszystkie',
                                ],
                                [
                                    'value' => $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PENDING]),
                                    'label' => 'Oczekujące',
                                ],
                                [
                                    'value' => $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::PROCESSING]),
                                    'label' => 'W trakcie',
                                ],
                                [
                                    'value' => $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::COMPLETED]),
                                    'label' => 'Zakończone',
                                ],
                                [
                                    'value' => $articleExportQueueRepository->count(['status' => ArticleExportQueueStatus::FAILED]),
                                    'label' => 'Błędy',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Otwórz kolejki',
                        'route' => 'admin_queue_status',
                    ],
                ],
                [
                    'label' => 'admin://users',
                    'title' => 'Użytkownicy',
                    'description' => 'Zarządzanie dostępem do panelu, aktywnością kont i podstawowymi danymi logowania.',
                    'stats' => [
                        [
                            'value' => $userRepository->count([]),
                            'label' => 'Wszystkie',
                        ],
                        [
                            'value' => $userRepository->countActive(),
                            'label' => 'Aktywne',
                        ],
                        [
                            'value' => $userRepository->countInactive(),
                            'label' => 'Nieaktywne',
                        ],
                        [
                            'value' => $userRepository->countAdministrators(),
                            'label' => 'Administratorzy',
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Otwórz użytkowników',
                        'route' => 'admin_user_index',
                    ],
                    'secondary_action' => [
                        'label' => 'Dodaj użytkownika',
                        'route' => 'admin_user_new',
                    ],
                ],
                [
                    'label' => 'admin://blog-settings',
                    'title' => 'Ustawienia bloga',
                    'description' => 'Konfiguracja nazwy bloga, SEO strony głównej i liczby wpisów na stronę.',
                    'meta_cards' => [
                        [
                            'label' => 'Tytuł bloga',
                            'value' => null !== $settings ? $settings->getBlogTitle() : 'Blog System AI',
                        ],
                        [
                            'label' => 'Artykułów na stronę',
                            'value' => null !== $settings ? (string) $settings->getArticlesPerPage() : '5',
                        ],
                        [
                            'label' => 'Ostatnia aktualizacja',
                            'value' => null !== $settings
                                ? $settings->getUpdatedAt()->format('Y-m-d H:i')
                                : 'Brak zapisanych zmian',
                        ],
                    ],
                    'meta' => [],
                    'primary_action' => [
                        'label' => 'Otwórz ustawienia',
                        'route' => 'admin_blog_settings',
                    ],
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

    private function resolveDashboardUser(): ?UserInterface
    {
        if (!isset($this->container)) {
            return null;
        }

        $user = $this->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
