<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleExport;
use App\Enum\ArticleExportStatus;
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
}
