<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleExport;
use App\Enum\ArticleExportStatus;
use App\Enum\ArticleExportType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleExport>
 */
class ArticleExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleExport::class);
    }

    public function countNew(): int
    {
        return $this->count([
            'status' => ArticleExportStatus::NEW,
        ]);
    }

    /**
     * @return list<ArticleExport>
     */
    public function findAllForAdminIndex(?ArticleExportType $type = null): array
    {
        $queryBuilder = $this->createAdminIndexQueryBuilder($type);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ArticleExport>
     */
    public function findPaginatedForAdminIndex(int $page, int $limit, ?ArticleExportType $type = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        return $this->createAdminIndexQueryBuilder($type)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdminIndex(?ArticleExportType $type = null): int
    {
        $queryBuilder = $this->createQueryBuilder('article_export')
            ->select('COUNT(article_export.id)');

        if ($type instanceof ArticleExportType) {
            $queryBuilder
                ->andWhere('article_export.type = :type')
                ->setParameter('type', $type);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminIndexQueryBuilder(?ArticleExportType $type)
    {
        $queryBuilder = $this->createQueryBuilder('article_export')
            ->leftJoin('article_export.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->orderBy('article_export.createdAt', 'DESC');

        if ($type instanceof ArticleExportType) {
            $queryBuilder
                ->andWhere('article_export.type = :type')
                ->setParameter('type', $type);
        }

        return $queryBuilder;
    }
}
