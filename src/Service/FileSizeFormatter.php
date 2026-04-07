<?php

declare(strict_types=1);

namespace App\Service;

class FileSizeFormatter
{
    public function format(int $bytes): string
    {
        if ($bytes < 1024) {
            return sprintf('%d B', $bytes);
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || 'TB' === $unit) {
                break;
            }

            $value /= 1024;
        }

        return sprintf('%.1f %s', $value, $unit);
    }
}
