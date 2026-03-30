<?php

declare(strict_types=1);

namespace App\Repository;

use BackedEnum;
use UnitEnum;

trait QueueStatusCountNormalizerTrait
{
    private function normalizeQueueStatusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        if ($status instanceof UnitEnum) {
            return $status->name;
        }

        return (string) $status;
    }
}
