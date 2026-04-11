<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleKeywordExportQueue;
use App\Entity\User;
use App\Enum\ArticleExportQueueStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleKeywordExportQueue>
 */
class ArticleKeywordExportQueueRepository extends ServiceEntityRepository
{
    use QueueStatusCountNormalizerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleKeywordExportQueue::class);
    }

    public function enqueuePending(?User $requestedBy = null): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        try {
            $insertedRows = $this->getEntityManager()->getConnection()->executeStatement(
                'INSERT INTO article_keyword_export_queue (requested_by_id, status, processed_at, created_at, updated_at)
                VALUES (:requestedById, :status, :processedAt, :createdAt, :updatedAt)',
                [
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
     * @return list<ArticleKeywordExportQueue>
     */
    public function findPendingOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->leftJoin('queue_item.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function claimNextPending(): ?ArticleKeywordExportQueue
    {
        $connection = $this->getEntityManager()->getConnection();
        $updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $maxRetries = 100;
        $attempt = 0;

        while ($attempt++ < $maxRetries) {
            $queueItemId = $connection->fetchOne(
                'SELECT id
                FROM article_keyword_export_queue
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
                'UPDATE article_keyword_export_queue
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

    /**
     * @return array<string, int>
     */
    public function countGroupedByStatus(): array
    {
        $rows = $this->createQueryBuilder('queue_item')
            ->select('queue_item.status AS status, COUNT(queue_item.id) AS items_count')
            ->groupBy('queue_item.status')
            ->getQuery()
            ->getArrayResult();

        $counts = ['all' => 0];
        foreach (ArticleExportQueueStatus::cases() as $status) {
            $counts[$status->value] = 0;
        }

        foreach ($rows as $row) {
            $status = $this->normalizeQueueStatusValue($row['status'] ?? '');
            $count = (int) ($row['items_count'] ?? 0);
            $counts[$status] = $count;
            $counts['all'] += $count;
        }

        return $counts;
    }
}
