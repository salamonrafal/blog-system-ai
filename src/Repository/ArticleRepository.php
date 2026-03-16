<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return list<Article>
     */
    public function findPublishedOrderedByDate(?ArticleLanguage $language = null): array
    {
        $queryBuilder = $this->createQueryBuilder('article')
            ->andWhere('article.status = :status')
            ->setParameter('status', ArticleStatus::PUBLISHED)
            ->orderBy('article.publishedAt', 'DESC')
            ->addOrderBy('article.createdAt', 'DESC');

        if (null !== $language) {
            $queryBuilder
                ->andWhere('article.language = :language')
                ->setParameter('language', $language);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('article')
            ->andWhere('article.slug = :slug')
            ->andWhere('article.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', ArticleStatus::PUBLISHED)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('article')
            ->andWhere('article.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('article')
            ->select('COUNT(article.id)')
            ->andWhere('article.slug = :slug')
            ->setParameter('slug', $slug);

        if (null !== $ignoreId) {
            $queryBuilder
                ->andWhere('article.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
