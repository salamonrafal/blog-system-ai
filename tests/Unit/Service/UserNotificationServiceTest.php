<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserNotificationServiceTest extends TestCase
{
    public function testConsumeUndisplayedForUserIdCollapsesDuplicateNotificationTypes(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');

        $firstImportNotification = new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS);
        $secondImportNotification = new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS);
        $exportNotification = new UserNotification($user, UserNotificationType::EXPORT_COMPLETED_ERROR);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findUndisplayedForUserId')
            ->with(7)
            ->willReturn([
                $firstImportNotification,
                $secondImportNotification,
                $exportNotification,
            ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);
        $managerRegistry
            ->expects($this->never())
            ->method('resetManager');

        $service = new UserNotificationService($managerRegistry);
        $notifications = $service->consumeUndisplayedForUserId(7);

        $this->assertCount(2, $notifications);
        $this->assertSame(
            [UserNotificationType::IMPORT_COMPLETED_SUCCESS, UserNotificationType::EXPORT_COMPLETED_ERROR],
            array_map(static fn (UserNotification $notification): UserNotificationType => $notification->getType(), $notifications),
        );
        $this->assertNotNull($firstImportNotification->getDisplayedAt());
        $this->assertNotNull($secondImportNotification->getDisplayedAt());
        $this->assertNotNull($exportNotification->getDisplayedAt());
    }

    public function testFindLatestForUserIdReturnsRepositoryResultsWithoutChangingNotifications(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');

        $notifications = [
            new UserNotification($user, UserNotificationType::EXPORT_COMPLETED_SUCCESS),
            new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_ERROR),
        ];

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findLatestForUserId')
            ->with(7, 10)
            ->willReturn($notifications);
        $repository
            ->expects($this->never())
            ->method('findUndisplayedForUserId');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
        $entityManager
            ->expects($this->never())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);
        $managerRegistry
            ->expects($this->never())
            ->method('resetManager');

        $service = new UserNotificationService($managerRegistry);

        $this->assertSame($notifications, $service->findLatestForUserId(7, 10));
        $this->assertNull($notifications[0]->getDisplayedAt());
        $this->assertNull($notifications[1]->getDisplayedAt());
    }

    public function testToggleReadStatusForUserIdMarksUnreadNotificationAsRead(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');

        $notification = new UserNotification($user, UserNotificationType::EXPORT_COMPLETED_SUCCESS);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneForUserId')
            ->with(7, 21)
            ->willReturn($notification);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);

        $service = new UserNotificationService($managerRegistry);
        $updatedNotification = $service->toggleReadStatusForUserId(7, 21);

        $this->assertSame($notification, $updatedNotification);
        $this->assertTrue($notification->isRead());
    }

    public function testDeleteForUserIdRemovesNotification(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');

        $notification = new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_ERROR);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneForUserId')
            ->with(7, 15)
            ->willReturn($notification);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($notification);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);

        $service = new UserNotificationService($managerRegistry);

        $this->assertTrue($service->deleteForUserId(7, 15));
    }

    public function testCountForUserIdReturnsRepositoryCount(): void
    {
        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('countForUserId')
            ->with(7)
            ->willReturn(14);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);

        $service = new UserNotificationService($managerRegistry);

        $this->assertSame(14, $service->countForUserId(7));
    }

    public function testDeleteAllForUserIdRemovesEveryNotification(): void
    {
        $user = (new User())
            ->setEmail('admin@example.com')
            ->setFullName('Admin');

        $notifications = [
            new UserNotification($user, UserNotificationType::IMPORT_COMPLETED_SUCCESS),
            new UserNotification($user, UserNotificationType::EXPORT_COMPLETED_ERROR),
        ];

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findAllForUserId')
            ->with(7)
            ->willReturn($notifications);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
        $entityManager
            ->expects($this->exactly(2))
            ->method('remove');
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(UserNotification::class)
            ->willReturn($entityManager);

        $service = new UserNotificationService($managerRegistry);

        $this->assertSame(2, $service->deleteAllForUserId(7));
    }
}
