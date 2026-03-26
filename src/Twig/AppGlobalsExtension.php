<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\BlogSettingsProvider;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly BlogSettingsProvider $blogSettingsProvider,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
        private readonly ArticleImportQueueRepository $articleImportQueueRepository,
        private readonly ArticleExportQueueRepository $articleExportQueueRepository,
        private readonly ArticleExportRepository $articleExportRepository,
        private readonly string $appEnv,
    )
    {
    }

    public function getGlobals(): array
    {
        $settings = $this->blogSettingsProvider->getSettings();
        $pendingImportCount = $this->articleImportQueueRepository->countPending();
        $pendingExportQueueCount = $this->articleExportQueueRepository->countPending();
        $newExportCount = $this->articleExportRepository->countNew();

        return [
            'app_name' => $settings->getBlogTitle(),
            'blog_settings' => $settings,
            'app_env' => $this->appEnv,
            'user_language' => $this->userLanguageResolver->getLanguage(),
            'user_timezone' => $this->userTimeZoneResolver->getTimeZone(),
            'admin_shortcut_badges' => [
                'queue_status' => $pendingImportCount + $pendingExportQueueCount,
                'imports' => $pendingImportCount,
                'exports' => $newExportCount,
                'import_export' => $pendingImportCount + $pendingExportQueueCount + $newExportCount,
            ],
        ];
    }
}
