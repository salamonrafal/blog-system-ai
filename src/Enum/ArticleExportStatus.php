<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleExportStatus: string
{
    case NEW = 'new';
    case DOWNLOADED = 'downloaded';
}
