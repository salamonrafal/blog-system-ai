<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleExportQueue;
use App\Entity\User;
use App\Enum\ArticleExportQueueStatus;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleExportQueue>
 */
class ArticleExportQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleExportQueue::class);
    }

    public function hasOpenQueueItemForArticle(Article $article): bool
    {
        return null !== $this->createQueryBuilder('queue_item')
            ->select('queue_item.id')
            ->andWhere('queue_item.article = :article')
            ->andWhere('queue_item.status IN (:statuses)')
            ->setParameter('article', $article)
            ->setParameter('statuses', [
                ArticleExportQueueStatus::PENDING,
                ArticleExportQueueStatus::PROCESSING,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function enqueuePending(Article $article, ?User $requestedBy = null): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        try {
            $insertedRows = $this->getEntityManager()->getConnection()->executeStatement(
                'INSERT INTO article_export_queue (article_id, requested_by_id, status, processed_at, created_at, updated_at)
                VALUES (:articleId, :requestedById, :status, :processedAt, :createdAt, :updatedAt)',
                [
                    'articleId' => $article->getId(),
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
     * @return list<ArticleExportQueue>
     */
    public function findPendingForArticle(Article $article): array
    {
        return $this->createQueryBuilder('queue_item')
            ->andWhere('queue_item.article = :article')
            ->andWhere('queue_item.status = :status')
            ->setParameter('article', $article)
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ArticleExportQueue>
     */
    public function findPendingOrderedByCreatedAt(): array
    {
        return $this->createQueryBuilder('queue_item')
            ->innerJoin('queue_item.article', 'article')
            ->addSelect('article')
            ->leftJoin('queue_item.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function claimNextPending(): ?ArticleExportQueue
    {
        $connection = $this->getEntityManager()->getConnection();
        $updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $maxRetries = 100;
        $attempt = 0;

        while ($attempt++ < $maxRetries) {
            $queueItemId = $connection->fetchOne(
                'SELECT id
                FROM article_export_queue
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
                'UPDATE article_export_queue
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
                ->innerJoin('queue_item.article', 'article')
                ->addSelect('article')
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
