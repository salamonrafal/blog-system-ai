<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findPublishedOrderedByDate(?ArticleLanguage $language = null, ?ArticleCategory $category = null): array
    {
        return $this->createPublishedOrderedByDateQueryBuilder($language, $category)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Article>
     */
    public function findPublishedPaginated(?ArticleLanguage $language, int $page, int $limit, ?ArticleCategory $category = null): array
    {
        return $this->createPublishedOrderedByDateQueryBuilder($language, $category)
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Article>
     */
    public function findPaginatedOrderedByCreatedDate(int $page, int $limit): array
    {
        return $this->createQueryBuilder('article')
            ->orderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublished(?ArticleLanguage $language = null, ?ArticleCategory $category = null): int
    {
        return (int) $this->createPublishedQueryBuilder($language, $category)
            ->select('COUNT(article.id)')
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @return list<Article>
     */
    public function findRecommendedPublished(Article $currentArticle, int $limit = 5): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->createPublishedOrderedByDateQueryBuilder(
            $currentArticle->getLanguage(),
            $currentArticle->getCategory(),
        )
            ->andWhere('article != :currentArticle')
            ->setParameter('currentArticle', $currentArticle)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    private function createPublishedOrderedByDateQueryBuilder(?ArticleLanguage $language, ?ArticleCategory $category): QueryBuilder
    {
        return $this->createPublishedQueryBuilder($language, $category)
            ->addSelect('COALESCE(article.publishedAt, article.createdAt) AS HIDDEN publicationOrderAt')
            ->orderBy('publicationOrderAt', 'DESC')
            ->addOrderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC');
    }

    private function createPublishedQueryBuilder(?ArticleLanguage $language, ?ArticleCategory $category): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('article')
            ->andWhere('article.status = :status')
            ->setParameter('status', ArticleStatus::PUBLISHED);

        if (null !== $language) {
            $queryBuilder
                ->andWhere('article.language = :language')
                ->setParameter('language', $language);
        }

        if (null !== $category) {
            $queryBuilder
                ->andWhere('article.category = :category')
                ->setParameter('category', $category);
        }

        return $queryBuilder;
    }
}
