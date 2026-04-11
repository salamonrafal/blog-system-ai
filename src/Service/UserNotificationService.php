<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserNotificationService
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function notifyImportCompleted(?int $userId, bool $success): void
    {
        $this->createNotification(
            $userId,
            $success ? UserNotificationType::IMPORT_COMPLETED_SUCCESS : UserNotificationType::IMPORT_COMPLETED_ERROR,
        );
    }

    public function notifyTopMenuImportCompleted(?int $userId, bool $success): void
    {
        $this->createNotification(
            $userId,
            $success ? UserNotificationType::TOP_MENU_IMPORT_COMPLETED_SUCCESS : UserNotificationType::TOP_MENU_IMPORT_COMPLETED_ERROR,
        );
    }

    public function notifyCategoryImportCompleted(?int $userId, bool $success): void
    {
        $this->createNotification(
            $userId,
            $success ? UserNotificationType::CATEGORY_IMPORT_COMPLETED_SUCCESS : UserNotificationType::CATEGORY_IMPORT_COMPLETED_ERROR,
        );
    }

    public function notifyKeywordImportCompleted(?int $userId, bool $success): void
    {
        $this->createNotification(
            $userId,
            $success ? UserNotificationType::KEYWORD_IMPORT_COMPLETED_SUCCESS : UserNotificationType::KEYWORD_IMPORT_COMPLETED_ERROR,
        );
    }

    public function notifyExportCompleted(?int $userId, bool $success): void
    {
        $this->createNotification(
            $userId,
            $success ? UserNotificationType::EXPORT_COMPLETED_SUCCESS : UserNotificationType::EXPORT_COMPLETED_ERROR,
        );
    }

    /**
     * @return list<UserNotification>
     */
    public function consumeUndisplayedForUserId(?int $userId): array
    {
        if (null === $userId) {
            return [];
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);
        $notifications = $this->getNotificationRepository($entityManager)->findUndisplayedForUserId($userId);
        if ([] === $notifications) {
            return [];
        }

        $displayedAt = $this->utcNow();

        foreach ($notifications as $notification) {
            $notification->setDisplayedAt($displayedAt);
        }

        $entityManager->flush();

        return $this->collapseNotifications($notifications);
    }

    /**
     * @return list<UserNotification>
     */
    public function findLatestForUserId(?int $userId, int $limit = 10): array
    {
        if (null === $userId) {
            return [];
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);

        return $this->getNotificationRepository($entityManager)->findLatestForUserId($userId, $limit);
    }

    public function toggleReadStatusForUserId(?int $userId, int $notificationId): ?UserNotification
    {
        if (null === $userId) {
            return null;
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);
        $notification = $this->getNotificationRepository($entityManager)->findOneForUserId($userId, $notificationId);
        if (!$notification instanceof UserNotification) {
            return null;
        }

        if ($notification->isRead()) {
            $notification->markAsUnread();
        } else {
            $notification->markAsRead();
        }

        $entityManager->flush();

        return $notification;
    }

    public function countForUserId(?int $userId): int
    {
        if (null === $userId) {
            return 0;
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);

        return $this->getNotificationRepository($entityManager)->countForUserId($userId);
    }

    public function deleteForUserId(?int $userId, int $notificationId): bool
    {
        if (null === $userId) {
            return false;
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);
        $notification = $this->getNotificationRepository($entityManager)->findOneForUserId($userId, $notificationId);
        if (!$notification instanceof UserNotification) {
            return false;
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        return true;
    }

    public function deleteAllForUserId(?int $userId): int
    {
        if (null === $userId) {
            return 0;
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);

        return $this->getNotificationRepository($entityManager)->deleteAllForUserId($userId);
    }

    private function createNotification(?int $userId, UserNotificationType $type): void
    {
        if (null === $userId) {
            return;
        }

        $entityManager = $this->getWritableEntityManager(UserNotification::class);
        $user = $entityManager->find(User::class, $userId);
        if (!$user instanceof User) {
            return;
        }

        $entityManager->persist(new UserNotification($user, $type));
        $entityManager->flush();
    }

    private function getWritableEntityManager(string $className): EntityManagerInterface
    {
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        if ($entityManager instanceof EntityManagerInterface && $entityManager->isOpen()) {
            return $entityManager;
        }

        $this->managerRegistry->resetManager();

        $entityManager = $this->managerRegistry->getManagerForClass($className);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('Entity manager for "%s" is not available.', $className));
        }

        return $entityManager;
    }

    private function getNotificationRepository(EntityManagerInterface $entityManager): UserNotificationRepository
    {
        $repository = $entityManager->getRepository(UserNotification::class);
        if (!$repository instanceof UserNotificationRepository) {
            throw new \RuntimeException('User notification repository is not available.');
        }

        return $repository;
    }

    private function utcNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    /**
     * @param list<UserNotification> $notifications
     *
     * @return list<UserNotification>
     */
    private function collapseNotifications(array $notifications): array
    {
        $collapsedNotifications = [];

        foreach ($notifications as $notification) {
            $key = $notification->getType()->value;
            if (!array_key_exists($key, $collapsedNotifications)) {
                $collapsedNotifications[$key] = $notification;
            }
        }

        return array_values($collapsedNotifications);
    }
}
