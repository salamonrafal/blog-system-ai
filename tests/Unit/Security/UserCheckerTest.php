<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use App\Tests\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class UserCheckerTest extends TestCase
{
    public function testCheckPreAuthThrowsExceptionForInactiveUser(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setPassword('secret')
            ->setIsActive(false);

        $checker = new UserChecker();

        $this->assertThrows(
            CustomUserMessageAccountStatusException::class,
            static fn () => $checker->checkPreAuth($user),
            'Your account is inactive.'
        );
    }

    public function testCheckPreAuthAllowsActiveApplicationUser(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setPassword('secret')
            ->setIsActive(true);

        $checker = new UserChecker();
        $checker->checkPreAuth($user);

        $this->assertTrue(true);
    }

    public function testCheckPreAuthIgnoresNonApplicationUsers(): void
    {
        $checker = new UserChecker();
        $checker->checkPreAuth(new InMemoryUser('user@example.com', 'secret'));

        $this->assertTrue(true);
    }
}
