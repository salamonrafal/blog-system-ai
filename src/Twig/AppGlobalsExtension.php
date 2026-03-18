<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\BlogSettingsProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly BlogSettingsProvider $blogSettingsProvider)
    {
    }

    public function getGlobals(): array
    {
        $settings = $this->blogSettingsProvider->getSettings();

        return [
            'app_name' => $settings->getBlogTitle(),
            'blog_settings' => $settings,
        ];
    }
}
