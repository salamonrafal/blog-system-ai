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
            ->method('flush');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->with(UserNotification::class)
            ->willReturn($repository);
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
}
