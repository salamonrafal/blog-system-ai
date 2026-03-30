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
