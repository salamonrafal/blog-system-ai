<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Service\BlogSettingsProvider;
use App\Service\UserNotificationService;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use App\Repository\ArticleExportQueueRepository;
use App\Repository\ArticleExportRepository;
use App\Repository\ArticleImportQueueRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly BlogSettingsProvider $blogSettingsProvider,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
        private readonly Security $security,
        private readonly UserNotificationService $userNotificationService,
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
        $user = $this->security->getUser();
        $userNotifications = $user instanceof User
            ? $this->userNotificationService->consumeUndisplayedForUserId($user->getId())
            : [];

        return [
            'app_name' => $settings->getBlogTitle(),
            'app_url' => $settings->getAppUrl(),
            'blog_settings' => $settings,
            'app_env' => $this->appEnv,
            'user_language' => $this->userLanguageResolver->getLanguage(),
            'user_timezone' => $this->userTimeZoneResolver->getTimeZone(),
            'user_notifications' => $userNotifications,
            'admin_shortcut_badges' => [
                'queue_status' => $pendingImportCount + $pendingExportQueueCount,
                'imports' => $pendingImportCount,
                'exports' => $newExportCount,
                'import_export' => $pendingImportCount + $pendingExportQueueCount + $newExportCount,
            ],
        ];
    }
}
