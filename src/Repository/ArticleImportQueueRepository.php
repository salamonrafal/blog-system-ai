<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleImportQueue;
use App\Enum\ArticleImportQueueStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleImportQueue>
 */
class ArticleImportQueueRepository extends ServiceEntityRepository
{
    use QueueStatusCountNormalizerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleImportQueue::class);
    }

    /**
     * @return list<ArticleImportQueue>
     */
    public function findAllForAdminIndex(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->leftJoin('queue_item.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->orderBy('queue_item.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ArticleImportQueue>
     */
    public function findPendingOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->leftJoin('queue_item.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleImportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function claimNextPending(): ?ArticleImportQueue
    {
        $connection = $this->getEntityManager()->getConnection();
        $updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $maxRetries = 100;
        $attempt = 0;

        while ($attempt++ < $maxRetries) {
            $queueItemId = $connection->fetchOne(
                'SELECT id
                FROM article_import_queue
                WHERE status = :pendingStatus
                ORDER BY created_at ASC
                LIMIT 1',
                [
                    'pendingStatus' => ArticleImportQueueStatus::PENDING->value,
                ],
            );

            if (false === $queueItemId) {
                return null;
            }

            $updatedRows = $connection->executeStatement(
                'UPDATE article_import_queue
                SET status = :processingStatus, updated_at = :updatedAt, error_message = NULL
                WHERE id = :id AND status = :pendingStatus',
                [
                    'processingStatus' => ArticleImportQueueStatus::PROCESSING->value,
                    'updatedAt' => $updatedAt,
                    'id' => (int) $queueItemId,
                    'pendingStatus' => ArticleImportQueueStatus::PENDING->value,
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
            'status' => ArticleImportQueueStatus::PENDING,
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
        foreach (ArticleImportQueueStatus::cases() as $status) {
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
