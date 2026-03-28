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
}
