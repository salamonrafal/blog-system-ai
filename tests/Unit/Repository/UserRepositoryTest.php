<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testArticleAuthorFilterQueryNormalizesPolishUppercaseCharacters(): void
    {
        $this->assertSame('łukasz', $this->normalizeAuthorSearchText(' ŁUKASZ '));
        $this->assertSame('żaneta', $this->normalizeAuthorSearchText('ŻANETA'));
    }

    private function normalizeAuthorSearchText(string $query): string
    {
        $method = new \ReflectionMethod(UserRepository::class, 'normalizeAuthorSearchText');

        return $method->invoke(null, $query);
    }
}
