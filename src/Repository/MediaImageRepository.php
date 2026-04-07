<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MediaImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findAllForAdminIndex(string $query = '', string $sort = 'desc'): array
    {
        return $this->createAdminIndexQueryBuilder($query, $sort)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<MediaImage>
     */
    public function findPaginatedForAdminIndex(int $page, int $limit, string $query = '', string $sort = 'desc'): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        return $this->createAdminIndexQueryBuilder($query, $sort)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countForAdminIndex(string $query = '', string $sort = 'desc'): int
    {
        return (int) $this->createAdminIndexQueryBuilder($query, $sort)
            ->select('COUNT(media_image.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
     * @return list<array{
     *     id: int|null,
     *     displayName: string,
     *     originalDisplayName: string,
     *     customName: string|null,
     *     publicPath: string,
     *     fileSize: int,
     *     mimeType: string
     * }>
     */
    public function findForHeadlineImagePicker(string $query = '', string $sort = 'desc', int $limit = 10): array
    {
        $normalizedQuery = trim($query);
        $normalizedSort = 'asc' === strtolower($sort) ? 'ASC' : 'DESC';
        $normalizedLimit = max(1, $limit);

        $queryBuilder = $this->createQueryBuilder('media_image')
            ->orderBy('media_image.createdAt', $normalizedSort)
            ->setMaxResults($normalizedLimit);

        if ('' !== $normalizedQuery) {
            $queryBuilder
                ->andWhere('LOWER(media_image.originalFilename) LIKE LOWER(:query) OR LOWER(COALESCE(media_image.customName, \'\')) LIKE LOWER(:query)')
                ->setParameter('query', '%'.$normalizedQuery.'%');
        }

        /** @var list<MediaImage> $mediaImages */
        $mediaImages = $queryBuilder
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (MediaImage $mediaImage): array => [
                'id' => $mediaImage->getId(),
                'displayName' => $mediaImage->getDisplayName(),
                'originalDisplayName' => $mediaImage->getOriginalDisplayName(),
                'customName' => $mediaImage->getCustomName(),
                'publicPath' => $mediaImage->getPublicPath(),
                'fileSize' => $mediaImage->getFileSize(),
                'mimeType' => $mediaImage->getMimeType(),
            ],
            $mediaImages,
        );
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

    private function createAdminIndexQueryBuilder(string $query = '', string $sort = 'desc'): QueryBuilder
    {
        $normalizedSort = 'asc' === strtolower($sort) ? 'ASC' : 'DESC';
        $queryBuilder = $this->createQueryBuilder('media_image')
            ->leftJoin('media_image.requestedBy', 'requested_by')
            ->addSelect('requested_by')
            ->orderBy('media_image.createdAt', $normalizedSort);

        $normalizedQuery = trim($query);
        if ('' === $normalizedQuery) {
            return $queryBuilder;
        }

        return $queryBuilder
            ->andWhere('
                (
                    media_image.customName IS NOT NULL
                    AND LOWER(media_image.customName) LIKE LOWER(:query)
                )
                OR
                (
                    media_image.customName IS NULL
                    AND LOWER(media_image.originalFilename) LIKE LOWER(:query)
                )
            ')
            ->setParameter('query', '%'.$normalizedQuery.'%');
    }
}
