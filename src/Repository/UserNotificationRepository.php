<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotification>
 */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    /**
     * @return list<UserNotification>
     */
    public function findUndisplayedForUserId(int $userId): array
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('IDENTITY(notification.recipient) = :userId')
            ->andWhere('notification.displayedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('notification.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<UserNotification>
     */
    public function findLatestForUserId(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('IDENTITY(notification.recipient) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('notification.createdAt', 'DESC')
            ->addOrderBy('notification.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserId(int $userId, int $notificationId): ?UserNotification
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('notification.id = :notificationId')
            ->andWhere('IDENTITY(notification.recipient) = :userId')
            ->setParameter('notificationId', $notificationId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countForUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('notification')
            ->select('COUNT(notification.id)')
            ->andWhere('IDENTITY(notification.recipient) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<UserNotification>
     */
    public function findAllForUserId(int $userId): array
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('IDENTITY(notification.recipient) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
