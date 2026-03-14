<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\TestCase;

final class UserTest extends TestCase
{
    public function testSetEmailNormalizesCaseAndWhitespace(): void
    {
        $user = (new User())->setEmail('  ADMIN@Example.COM ');

        $this->assertSame('admin@example.com', $user->getEmail());
        $this->assertSame('admin@example.com', $user->getUserIdentifier());
    }

    public function testGetRolesAlwaysIncludesRoleUserWithoutDuplicates(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
