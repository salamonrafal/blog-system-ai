<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleKeyword;
use App\Enum\ArticleCategoryStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleKeyword>
 */
class ArticleKeywordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleKeyword::class);
    }

    /**
     * @return list<ArticleKeyword>
     */
    public function findForAdminIndex(): array
    {
        /** @var list<ArticleKeyword> $keywords */
        $keywords = $this->createQueryBuilder('keyword')
            ->orderBy('keyword.language', 'ASC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    /**
     * @return list<ArticleKeyword>
     */
    public function findForArticleAssignment(): array
    {
        /** @var list<ArticleKeyword> $keywords */
        $keywords = $this->createQueryBuilder('keyword')
            ->orderBy('keyword.status', 'ASC')
            ->addOrderBy('keyword.language', 'ASC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    public function countActive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::ACTIVE]);
    }

    public function countInactive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::INACTIVE]);
    }

    public function nameExistsForLanguage(string $name, string $language, ?int $ignoreId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('keyword')
            ->select('COUNT(keyword.id)')
            ->andWhere('keyword.name = :name')
            ->andWhere('keyword.language = :language')
            ->setParameter('name', $name)
            ->setParameter('language', strtolower(trim($language)));

        if (null !== $ignoreId) {
            $queryBuilder
                ->andWhere('keyword.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
