<?php

declare(strict_types=1);

namespace App\Service;

final class UploadLimitResolver
{
    /**
     * @param null|\Closure(string): string|false $iniReader
     */
    public function __construct(private readonly ?\Closure $iniReader = null)
    {
    }

    public function resolveEffectiveLimit(?int $applicationLimitBytes = null): ?int
    {
        $limits = array_values(array_filter([
            $this->normalizeLimit($applicationLimitBytes),
            $this->parseIniSize($this->readIni('upload_max_filesize')),
            $this->parseIniSize($this->readIni('post_max_size')),
        ], static fn (?int $limit): bool => null !== $limit));

        if ([] === $limits) {
            return null;
        }

        return min($limits);
    }

    private function normalizeLimit(?int $limit): ?int
    {
        return \is_int($limit) && $limit > 0 ? $limit : null;
    }

    private function parseIniSize(string|false $value): ?int
    {
        if (false === $value) {
            return null;
        }

        $normalizedValue = strtolower(trim($value));
        if ('' === $normalizedValue) {
            return null;
        }

        if ('0' === $normalizedValue || '-1' === $normalizedValue) {
            return null;
        }

        if (!preg_match('/^(?<size>\d+)(?<unit>[kmgt]?)$/', $normalizedValue, $matches)) {
            return null;
        }

        $size = (int) $matches['size'];
        if ($size <= 0) {
            return null;
        }

        return match ($matches['unit']) {
            'k' => $size * 1024,
            'm' => $size * 1024 * 1024,
            'g' => $size * 1024 * 1024 * 1024,
            't' => $size * 1024 * 1024 * 1024 * 1024,
            default => $size,
        };
    }

    private function readIni(string $key): string|false
    {
        return null !== $this->iniReader ? ($this->iniReader)($key) : ini_get($key);
    }
}
