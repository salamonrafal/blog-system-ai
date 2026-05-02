<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testArticleAuthorFilterQueryMatchesPolishUppercaseFullName(): void
    {
        $user = (new User())
            ->setEmail('lukasz@example.com')
            ->setFullName('Łukasz Kowalski');

        $this->assertTrue($this->matchesArticleAuthorFilterQuery($user, 'łukasz'));
    }

    public function testArticleAuthorFilterQueryMatchesPolishUppercaseNickname(): void
    {
        $user = (new User())
            ->setEmail('author@example.com')
            ->setNickname('Żaneta');

        $this->assertTrue($this->matchesArticleAuthorFilterQuery($user, 'żan'));
    }

    public function testArticleAuthorFilterQueryRejectsUnrelatedText(): void
    {
        $user = (new User())
            ->setEmail('author@example.com')
            ->setFullName('Łukasz Kowalski');

        $this->assertFalse($this->matchesArticleAuthorFilterQuery($user, 'anna'));
    }

    private function matchesArticleAuthorFilterQuery(User $user, string $query): bool
    {
        $method = new \ReflectionMethod(UserRepository::class, 'matchesArticleAuthorFilterQuery');

        return $method->invoke(null, $user, $query);
    }
}
