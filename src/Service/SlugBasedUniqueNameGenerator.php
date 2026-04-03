<?php

declare(strict_types=1);

namespace App\Service;

final class SlugBasedUniqueNameGenerator
{
    private const MAX_UNIQUE_NAME_LENGTH = 255;

    public function __construct(
        private readonly ArticleSlugger $articleSlugger,
    ) {
    }

    /**
     * @param callable(string): bool $exists
     */
    public function generate(string $baseValue, callable $exists, string $fallback): string
    {
        $baseUniqueName = $this->truncateUniqueName($this->articleSlugger->slugify($baseValue));
        $uniqueName = '' !== $baseUniqueName ? $baseUniqueName : $fallback;
        $baseUniqueName = $uniqueName;
        $counter = 2;

        while ($exists($uniqueName)) {
            $suffix = sprintf('-%d', $counter);
            $uniqueName = $this->truncateUniqueName($baseUniqueName, strlen($suffix)).$suffix;
            ++$counter;
        }

        return $uniqueName;
    }

    private function truncateUniqueName(string $uniqueName, int $reservedSuffixLength = 0): string
    {
        $maxBaseLength = max(1, self::MAX_UNIQUE_NAME_LENGTH - $reservedSuffixLength);

        return rtrim(substr($uniqueName, 0, $maxBaseLength), '-');
    }
}
