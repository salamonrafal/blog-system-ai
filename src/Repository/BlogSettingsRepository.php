<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogSettings>
 */
class BlogSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogSettings::class);
    }

    public function findCurrent(): ?BlogSettings
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
