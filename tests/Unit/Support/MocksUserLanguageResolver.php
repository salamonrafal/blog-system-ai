<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Service\UserLanguageResolver;

trait MocksUserLanguageResolver
{
    private function createUserLanguageResolverMock(string $language): UserLanguageResolver
    {
        $resolver = $this->createMock(UserLanguageResolver::class);
        $resolver
            ->method('translate')
            ->willReturnCallback(static fn (string $polish, string $english): string => 'pl' === $language ? $polish : $english);

        return $resolver;
    }
}
