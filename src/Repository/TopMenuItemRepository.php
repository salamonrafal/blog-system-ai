<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TopMenuItem;
use App\Enum\TopMenuItemStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TopMenuItem>
 */
class TopMenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopMenuItem::class);
    }

    /**
     * @return list<TopMenuItem>
     */
    public function findForAdminIndex(): array
    {
        /** @var list<TopMenuItem> $items */
        $items = $this->createBaseQueryBuilder()
            ->getQuery()
            ->getResult();

        return $items;
    }

    /**
     * @return list<TopMenuItem>
     */
    public function findActiveOrdered(): array
    {
        /** @var list<TopMenuItem> $items */
        $items = $this->createBaseQueryBuilder()
            ->andWhere('menu_item.status = :status')
            ->setParameter('status', TopMenuItemStatus::ACTIVE)
            ->getQuery()
            ->getResult();

        return $items;
    }

    public function countActive(): int
    {
        return $this->count(['status' => TopMenuItemStatus::ACTIVE]);
    }

    public function countInactive(): int
    {
        return $this->count(['status' => TopMenuItemStatus::INACTIVE]);
    }

    public function findOneByUniqueName(string $uniqueName): ?TopMenuItem
    {
        return $this->findOneBy(['uniqueName' => $uniqueName]);
    }

    /**
     * @param list<string> $uniqueNames
     *
     * @return array<string, TopMenuItem>
     */
    public function findByUniqueNames(array $uniqueNames): array
    {
        $uniqueNames = array_values(array_unique(array_filter(array_map(
            static fn (string $uniqueName): string => trim($uniqueName),
            $uniqueNames,
        ), static fn (string $uniqueName): bool => '' !== $uniqueName)));

        if ([] === $uniqueNames) {
            return [];
        }

        /** @var list<TopMenuItem> $items */
        $items = $this->createBaseQueryBuilder()
            ->andWhere('menu_item.uniqueName IN (:uniqueNames)')
            ->setParameter('uniqueNames', $uniqueNames)
            ->getQuery()
            ->getResult();

        $indexedItems = [];
        foreach ($items as $item) {
            $indexedItems[$item->getUniqueName()] = $item;
        }

        return $indexedItems;
    }

    public function uniqueNameExists(string $uniqueName, ?int $ignoreId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('menu_item')
            ->select('COUNT(menu_item.id)')
            ->andWhere('menu_item.uniqueName = :uniqueName')
            ->setParameter('uniqueName', $uniqueName);

        if (null !== $ignoreId) {
            $queryBuilder
                ->andWhere('menu_item.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('menu_item')
            ->leftJoin('menu_item.parent', 'parent')
            ->addSelect('parent')
            ->leftJoin('menu_item.articleCategory', 'article_category')
            ->addSelect('article_category')
            ->leftJoin('menu_item.article', 'article')
            ->addSelect('article')
            ->orderBy('menu_item.position', 'ASC')
            ->addOrderBy('menu_item.id', 'ASC');
    }
}
