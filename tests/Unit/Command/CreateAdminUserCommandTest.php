<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CreateAdminUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminUserCommandTest extends TestCase
{
    public function testExecuteCreatesActiveAdministratorWhenDataIsValid(): void
    {
        $capturedUser = null;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (User $user) use (&$capturedUser): void {
                $capturedUser = $user;
            });
        $entityManager->expects($this->once())->method('flush');

        $userRepository = $this->createUserRepositoryMock(null);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $tester = new CommandTester(new CreateAdminUserCommand($entityManager, $userRepository, $passwordHasher));
        $exitCode = $tester->execute([
            'email' => 'admin@example.com',
            'password' => 'super-secret',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertInstanceOf(User::class, $capturedUser);
        $this->assertSame('admin@example.com', $capturedUser->getEmail());
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $capturedUser->getRoles());
        $this->assertTrue($capturedUser->isActive());
        $this->assertSame('hashed-password', $capturedUser->getPassword());
        $this->assertStringContainsString('Administrator admin@example.com has been created.', $tester->getDisplay());
    }

    public function testExecuteCreatesInactiveAdministratorWhenOptionIsUsed(): void
    {
        $capturedUser = null;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(static function (User $user) use (&$capturedUser): void {
                $capturedUser = $user;
            });
        $entityManager->expects($this->once())->method('flush');

        $userRepository = $this->createUserRepositoryMock(null);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed-password');

        $tester = new CommandTester(new CreateAdminUserCommand($entityManager, $userRepository, $passwordHasher));
        $exitCode = $tester->execute([
            'email' => 'admin@example.com',
            'password' => 'super-secret',
            '--inactive' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertInstanceOf(User::class, $capturedUser);
        $this->assertFalse($capturedUser->isActive());
    }

    public function testExecuteFailsWhenUserWithEmailAlreadyExists(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $existingUser = (new User())
            ->setEmail('admin@example.com')
            ->setPassword('hashed-password');

        $userRepository = $this->createUserRepositoryMock($existingUser);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->never())->method('hashPassword');

        $tester = new CommandTester(new CreateAdminUserCommand($entityManager, $userRepository, $passwordHasher));
        $exitCode = $tester->execute([
            'email' => 'admin@example.com',
            'password' => 'super-secret',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('A user with this email already exists.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenPasswordIsBlank(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $userRepository = $this->createUserRepositoryMock(null);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->never())->method('hashPassword');

        $tester = new CommandTester(new CreateAdminUserCommand($entityManager, $userRepository, $passwordHasher));
        $exitCode = $tester->execute([
            'email' => 'admin@example.com',
            'password' => '   ',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Password is required.', $tester->getDisplay());
    }

    private function createUserRepositoryMock(?User $user): UserRepository
    {
        /** @var UserRepository&MockObject $repository */
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->method('findOneByEmail')
            ->willReturn($user);

        return $repository;
    }
}
