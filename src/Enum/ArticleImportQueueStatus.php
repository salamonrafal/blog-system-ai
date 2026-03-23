<?php

declare(strict_types=1);

namespace App\Enum;

enum ArticleImportQueueStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
