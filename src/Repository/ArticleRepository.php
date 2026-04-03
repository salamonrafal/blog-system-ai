<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\ArticleKeyword;
use App\Entity\BlogSettings;
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
    public const TOP_MENU_SELECTION_LIMIT = 100;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return list<Article>
     */
    public function findPublishedOrderedByDate(
        ?ArticleLanguage $language = null,
        ?ArticleCategory $category = null,
        ?ArticleKeyword $keyword = null,
    ): array
    {
        return $this->createPublishedOrderedByDateQueryBuilder($language, $category, $keyword)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Article>
     */
    public function findPublishedPaginated(
        ?ArticleLanguage $language,
        int $page,
        int $limit,
        ?ArticleCategory $category = null,
        ?ArticleKeyword $keyword = null,
    ): array
    {
        return $this->createPublishedOrderedByDateQueryBuilder($language, $category, $keyword)
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Article>
     */
    public function findPaginatedOrderedByCreatedDate(int $page, int $limit, ?ArticleCategory $category = null): array
    {
        return $this->createAdminIndexQueryBuilder($category)
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdminIndex(?ArticleCategory $category = null): int
    {
        return (int) $this->createAdminIndexFilterQueryBuilder($category)
            ->select('COUNT(article.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublished(
        ?ArticleLanguage $language = null,
        ?ArticleCategory $category = null,
        ?ArticleKeyword $keyword = null,
    ): int
    {
        return (int) $this->createPublishedQueryBuilder($language, $category, $keyword)
            ->select('COUNT(DISTINCT article.id)')
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
    public function findRecommendedPublished(Article $currentArticle, int $limit = BlogSettings::DEFAULT_RECOMMENDED_ARTICLES_LIMIT): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->createPublishedOrderedByDateQueryBuilder(
            $currentArticle->getLanguage(),
            $currentArticle->getCategory(),
            null,
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

    /**
     * @return list<Article>
     */
    public function findRecentForTopMenuSelection(int $limit = self::TOP_MENU_SELECTION_LIMIT): array
    {
        if ($limit <= 0) {
            return [];
        }

        /** @var list<Article> $articles */
        $articles = $this->createPublishedOrderedByDateQueryBuilder(null, null, null)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $articles;
    }

    private function createPublishedOrderedByDateQueryBuilder(
        ?ArticleLanguage $language,
        ?ArticleCategory $category,
        ?ArticleKeyword $keyword,
    ): QueryBuilder
    {
        return $this->createPublishedQueryBuilder($language, $category, $keyword)
            ->addSelect('COALESCE(article.publishedAt, article.createdAt) AS HIDDEN publicationOrderAt')
            ->orderBy('publicationOrderAt', 'DESC')
            ->addOrderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC');
    }

    private function createPublishedQueryBuilder(
        ?ArticleLanguage $language,
        ?ArticleCategory $category,
        ?ArticleKeyword $keyword,
    ): QueryBuilder
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

        if (null !== $keyword) {
            $queryBuilder
                ->innerJoin('article.keywords', 'keyword')
                ->andWhere('keyword = :keyword')
                ->setParameter('keyword', $keyword);
        }

        return $queryBuilder;
    }

    private function createAdminIndexQueryBuilder(?ArticleCategory $category): QueryBuilder
    {
        return $this->createAdminIndexFilterQueryBuilder($category)
            ->orderBy('article.createdAt', 'DESC')
            ->addOrderBy('article.id', 'DESC');
    }

    private function createAdminIndexFilterQueryBuilder(?ArticleCategory $category): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('article');

        if (null !== $category) {
            $queryBuilder
                ->andWhere('article.category = :category')
                ->setParameter('category', $category);
        }

        return $queryBuilder;
    }
}
