<?php

declare(strict_types=1);

namespace App\Entity;

trait EntityTextNormalizationTrait
{
    private function normalizeNullableText(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim($value);

        return '' === $normalized ? null : $normalized;
    }

    private function normalizeTranslations(array $translations): array
    {
        $normalized = [];

        foreach ($translations as $language => $value) {
            if (!is_scalar($language)) {
                continue;
            }

            $language = strtolower(trim((string) $language));

            if ('' === $language) {
                continue;
            }

            $normalizedValue = $this->normalizeNullableText(null !== $value ? (string) $value : null);
            if (null === $normalizedValue) {
                continue;
            }

            $normalized[$language] = $normalizedValue;
        }

        ksort($normalized);

        return $normalized;
    }
}
