<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\BlogSettingsProvider;
use App\Service\UserLanguageResolver;
use App\Service\UserTimeZoneResolver;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly BlogSettingsProvider $blogSettingsProvider,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UserTimeZoneResolver $userTimeZoneResolver,
    )
    {
    }

    public function getGlobals(): array
    {
        $settings = $this->blogSettingsProvider->getSettings();

        return [
            'app_name' => $settings->getBlogTitle(),
            'blog_settings' => $settings,
            'user_language' => $this->userLanguageResolver->getLanguage(),
            'user_timezone' => $this->userTimeZoneResolver->getTimeZone(),
        ];
    }
}
