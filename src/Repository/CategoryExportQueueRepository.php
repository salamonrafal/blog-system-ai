<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleCategory;
use App\Entity\CategoryExportQueue;
use App\Entity\User;
use App\Enum\ArticleExportQueueStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryExportQueue>
 */
class CategoryExportQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryExportQueue::class);
    }

    public function enqueuePending(ArticleCategory $category, ?User $requestedBy = null): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        try {
            $insertedRows = $this->getEntityManager()->getConnection()->executeStatement(
                'INSERT INTO category_export_queue (category_id, requested_by_id, status, processed_at, created_at, updated_at)
                VALUES (:categoryId, :requestedById, :status, :processedAt, :createdAt, :updatedAt)',
                [
                    'categoryId' => $category->getId(),
                    'requestedById' => $requestedBy?->getId(),
                    'status' => ArticleExportQueueStatus::PENDING->value,
                    'processedAt' => null,
                    'createdAt' => $now,
                    'updatedAt' => $now,
                ],
                [
                    'processedAt' => Types::DATETIME_IMMUTABLE,
                    'createdAt' => Types::DATETIME_IMMUTABLE,
                    'updatedAt' => Types::DATETIME_IMMUTABLE,
                ],
            );
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return 1 === $insertedRows;
    }

    /**
     * @return list<CategoryExportQueue>
     */
    public function findPendingForCategory(ArticleCategory $category): array
    {
        return $this->createQueryBuilder('queue_item')
            ->andWhere('queue_item.category = :category')
            ->andWhere('queue_item.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CategoryExportQueue>
     */
    public function findPendingOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->innerJoin('queue_item.category', 'category')
            ->addSelect('category')
            ->leftJoin('queue_item.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function claimNextPending(): ?CategoryExportQueue
    {
        $connection = $this->getEntityManager()->getConnection();
        $updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $maxRetries = 100;
        $attempt = 0;

        while ($attempt++ < $maxRetries) {
            $queueItemId = $connection->fetchOne(
                'SELECT id
                FROM category_export_queue
                WHERE status = :pendingStatus
                ORDER BY created_at ASC
                LIMIT 1',
                [
                    'pendingStatus' => ArticleExportQueueStatus::PENDING->value,
                ],
            );

            if (false === $queueItemId) {
                return null;
            }

            $updatedRows = $connection->executeStatement(
                'UPDATE category_export_queue
                SET status = :processingStatus, updated_at = :updatedAt
                WHERE id = :id AND status = :pendingStatus',
                [
                    'processingStatus' => ArticleExportQueueStatus::PROCESSING->value,
                    'updatedAt' => $updatedAt,
                    'id' => (int) $queueItemId,
                    'pendingStatus' => ArticleExportQueueStatus::PENDING->value,
                ],
                [
                    'updatedAt' => Types::DATETIME_IMMUTABLE,
                ],
            );

            if (1 !== $updatedRows) {
                continue;
            }

            return $this->createQueryBuilder('queue_item')
                ->innerJoin('queue_item.category', 'category')
                ->addSelect('category')
                ->leftJoin('queue_item.requestedBy', 'requested_by')
                ->addSelect('requested_by')
                ->andWhere('queue_item.id = :id')
                ->setParameter('id', (int) $queueItemId)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return null;
    }

    public function countPending(): int
    {
        return $this->count([
            'status' => ArticleExportQueueStatus::PENDING,
        ]);
    }
}
