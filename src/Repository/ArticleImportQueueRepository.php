<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleImportQueue;
use App\Enum\ArticleImportQueueStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleImportQueue>
 */
class ArticleImportQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleImportQueue::class);
    }

    /**
     * @return list<ArticleImportQueue>
     */
    public function findPendingOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleImportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return $this->count([
            'status' => ArticleImportQueueStatus::PENDING,
        ]);
    }
}
