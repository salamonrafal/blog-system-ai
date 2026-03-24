<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

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

    public function testProfileFieldsNormalizeWhitespaceAndEmptyValues(): void
    {
        $user = (new User())
            ->setFullName('  Jan Kowalski  ')
            ->setNickname('  janko  ')
            ->setShortBio('  Autor i redaktor  ')
            ->setAvatar('  avatar.webp  ');

        $this->assertSame('Jan Kowalski', $user->getFullName());
        $this->assertSame('janko', $user->getNickname());
        $this->assertSame('Autor i redaktor', $user->getShortBio());
        $this->assertSame('avatar.webp', $user->getAvatar());

        $user
            ->setFullName('   ')
            ->setNickname('')
            ->setShortBio(' ')
            ->setAvatar("\t");

        $this->assertNull($user->getFullName());
        $this->assertNull($user->getNickname());
        $this->assertNull($user->getShortBio());
        $this->assertNull($user->getAvatar());
    }

    public function testDisplayNameFallsBackThroughProfileFieldsToEmail(): void
    {
        $user = (new User())->setEmail('author@example.com');

        $this->assertSame('author@example.com', $user->getDisplayName());

        $user->setNickname('autor');
        $this->assertSame('autor', $user->getDisplayName());

        $user->setFullName('Jan Autor');
        $this->assertSame('Jan Autor', $user->getDisplayName());
    }
}
