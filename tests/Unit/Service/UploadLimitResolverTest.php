<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UploadLimitResolver;
use PHPUnit\Framework\TestCase;

final class UploadLimitResolverTest extends TestCase
{
    public function testResolveEffectiveLimitUsesSmallestPositiveConstraint(): void
    {
        $resolver = new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
            'upload_max_filesize' => '2M',
            'post_max_size' => '8M',
            default => false,
        });

        $this->assertSame(2 * 1024 * 1024, $resolver->resolveEffectiveLimit(5 * 1024 * 1024));
    }

    public function testResolveEffectiveLimitFallsBackToApplicationLimitWhenPhpLimitsAreUnavailable(): void
    {
        $resolver = new UploadLimitResolver(static fn (string $key): string|false => false);

        $this->assertSame(5 * 1024 * 1024, $resolver->resolveEffectiveLimit(5 * 1024 * 1024));
    }
}
