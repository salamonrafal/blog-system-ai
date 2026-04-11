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
     * @return list<array{item: TopMenuItem, children: list<array{item: TopMenuItem, children: array}>}>
     */
    public function findTreeForAdmin(): array
    {
        return $this->buildAdminTree($this->findForAdminIndex());
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

    /**
     * @param list<int> $orderedIds
     */
    public function reorderSiblings(?int $parentId, array $orderedIds): bool
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $orderedIds,
        ), static fn (int $value): bool => $value > 0)));

        if ([] === $normalizedIds) {
            return false;
        }

        $queryBuilder = $this->createBaseQueryBuilder();
        if (null === $parentId) {
            $queryBuilder->andWhere('menu_item.parent IS NULL');
        } else {
            $queryBuilder
                ->andWhere('parent.id = :parentId')
                ->setParameter('parentId', $parentId);
        }

        /** @var list<TopMenuItem> $siblings */
        $siblings = $queryBuilder->getQuery()->getResult();
        if (count($siblings) !== count($normalizedIds)) {
            return false;
        }

        $siblingsById = [];
        foreach ($siblings as $sibling) {
            $siblingId = $sibling->getId();
            if (null === $siblingId) {
                return false;
            }

            $siblingsById[$siblingId] = $sibling;
        }

        $siblingIds = array_keys($siblingsById);
        sort($siblingIds);
        $sortedOrderedIds = $normalizedIds;
        sort($sortedOrderedIds);

        if ($siblingIds !== $sortedOrderedIds) {
            return false;
        }

        foreach ($normalizedIds as $position => $id) {
            $siblingsById[$id]->setPosition($position);
        }

        return true;
    }

    public function applySiblingPositioning(TopMenuItem $menuItem, ?int $originalParentId = null, bool $normalizeOriginalBranch = false): void
    {
        $menuItemId = $menuItem->getId();
        if ($normalizeOriginalBranch) {
            $this->normalizeBranchPositions($originalParentId, $menuItemId);
        }

        $targetParentId = $menuItem->getParent()?->getId();
        $siblings = $this->findSiblingsByParentId($targetParentId, $menuItemId);
        $targetPosition = max(0, min($menuItem->getPosition(), count($siblings)));

        array_splice($siblings, $targetPosition, 0, [$menuItem]);

        foreach ($siblings as $position => $sibling) {
            $sibling->setPosition($position);
        }
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

    /**
     * @return list<TopMenuItem>
     */
    private function findSiblingsByParentId(?int $parentId, ?int $excludeId = null): array
    {
        $queryBuilder = $this->createBaseQueryBuilder();
        if (null === $parentId) {
            $queryBuilder->andWhere('menu_item.parent IS NULL');
        } else {
            $queryBuilder
                ->andWhere('parent.id = :parentId')
                ->setParameter('parentId', $parentId);
        }

        if (null !== $excludeId) {
            $queryBuilder
                ->andWhere('menu_item.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        /** @var list<TopMenuItem> $siblings */
        $siblings = $queryBuilder->getQuery()->getResult();

        return $siblings;
    }

    private function normalizeBranchPositions(?int $parentId, ?int $excludeId = null): void
    {
        foreach ($this->findSiblingsByParentId($parentId, $excludeId) as $position => $sibling) {
            $sibling->setPosition($position);
        }
    }

    /**
     * @param list<TopMenuItem> $items
     *
     * @return list<array{item: TopMenuItem, children: list<array{item: TopMenuItem, children: array}>}>
     */
    private function buildAdminTree(array $items): array
    {
        $itemsById = [];
        $childrenByParentId = [];
        $roots = [];

        foreach ($items as $item) {
            $itemId = $item->getId();
            if (null !== $itemId) {
                $itemsById[$itemId] = $item;
            }
        }

        foreach ($items as $item) {
            $parentId = $item->getParent()?->getId();
            if (null === $parentId || !isset($itemsById[$parentId])) {
                $roots[] = $item;

                continue;
            }

            $childrenByParentId[$parentId][] = $item;
        }

        return $this->buildAdminBranch($roots, $childrenByParentId);
    }

    /**
     * @param list<TopMenuItem> $items
     * @param array<int, list<TopMenuItem>> $childrenByParentId
     *
     * @return list<array{item: TopMenuItem, children: list<array{item: TopMenuItem, children: array}>}>
     */
    private function buildAdminBranch(array $items, array $childrenByParentId): array
    {
        $branch = [];

        foreach ($items as $item) {
            $itemId = $item->getId();
            $branch[] = [
                'item' => $item,
                'children' => null !== $itemId ? $this->buildAdminBranch($childrenByParentId[$itemId] ?? [], $childrenByParentId) : [],
            ];
        }

        return $branch;
    }
}
