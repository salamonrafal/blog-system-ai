<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MediaImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaImage>
 */
class MediaImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaImage::class);
    }

    /**
     * @return list<MediaImage>
     */
    public function findAllForAdminIndex(): array
    {
        return $this->createQueryBuilder('media_image')
            ->leftJoin('media_image.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->orderBy('media_image.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function customNameExists(string $customName, ?int $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('media_image')
            ->select('COUNT(media_image.id)')
            ->andWhere('LOWER(media_image.customName) = LOWER(:customName)')
            ->setParameter('customName', trim($customName));

        if (null !== $excludedId) {
            $queryBuilder
                ->andWhere('media_image.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return list<string>
     */
    public function findAllStoredFilePaths(): array
    {
        $rows = $this->createQueryBuilder('media_image')
            ->select('media_image.filePath')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['filePath'] ?? '')),
            $rows,
        ), static fn (string $path): bool => '' !== $path));
    }
}
