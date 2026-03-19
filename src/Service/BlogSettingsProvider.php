<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BlogSettings;
use App\Repository\BlogSettingsRepository;

class BlogSettingsProvider
{
    private ?BlogSettings $cachedSettings = null;

    public function __construct(private readonly BlogSettingsRepository $blogSettingsRepository)
    {
    }

    public function getSettings(): BlogSettings
    {
        if (null !== $this->cachedSettings) {
            return $this->cachedSettings;
        }

        return $this->cachedSettings = $this->blogSettingsRepository->findCurrent() ?? new BlogSettings();
    }
}
