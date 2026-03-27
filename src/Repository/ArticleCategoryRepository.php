<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleCategory;
use App\Enum\ArticleCategoryStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleCategory>
 */
class ArticleCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleCategory::class);
    }

    /**
     * @return list<ArticleCategory>
     */
    public function findForAdminIndex(): array
    {
        /** @var list<ArticleCategory> $categories */
        $categories = $this->createQueryBuilder('category')
            ->orderBy('category.createdAt', 'DESC')
            ->addOrderBy('category.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $categories;
    }

    public function countActive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::ACTIVE]);
    }

    public function countInactive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::INACTIVE]);
    }

    /**
     * @return list<ArticleCategory>
     */
    public function findActiveOrderedByName(): array
    {
        /** @var list<ArticleCategory> $categories */
        $categories = $this->createQueryBuilder('category')
            ->andWhere('category.status = :status')
            ->setParameter('status', ArticleCategoryStatus::ACTIVE)
            ->orderBy('category.name', 'ASC')
            ->addOrderBy('category.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $categories;
    }
}
