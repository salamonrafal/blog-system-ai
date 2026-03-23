<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleExportQueue;
use App\Enum\ArticleExportQueueStatus;
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
            ->andWhere('queue_item.status = :status')
            ->setParameter('status', ArticleExportQueueStatus::PENDING)
            ->orderBy('queue_item.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return $this->count([
            'status' => ArticleExportQueueStatus::PENDING,
        ]);
    }
}
