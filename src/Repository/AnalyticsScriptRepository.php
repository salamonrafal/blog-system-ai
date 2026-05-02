<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AnalyticsScript;
use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnalyticsScript>
 */
class AnalyticsScriptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsScript::class);
    }

    /**
     * @return list<AnalyticsScript>
     */
    public function findForAdminIndex(): array
    {
        return $this->createQueryBuilder('script')
            ->orderBy('script.position', 'ASC')
            ->addOrderBy('script.name', 'ASC')
            ->addOrderBy('script.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AnalyticsScript>
     */
    public function findEnabledForPlacementAndScopes(AnalyticsScriptPlacement $placement, array $scopes): array
    {
        $scopeValues = array_map(
            static fn (AnalyticsScriptScope $scope): string => $scope->value,
            $scopes,
        );

        return $this->createQueryBuilder('script')
            ->andWhere('script.enabled = :enabled')
            ->andWhere('script.placement = :placement')
            ->andWhere('script.scope IN (:scopes)')
            ->setParameter('enabled', true)
            ->setParameter('placement', $placement->value)
            ->setParameter('scopes', $scopeValues)
            ->orderBy('script.position', 'ASC')
            ->addOrderBy('script.name', 'ASC')
            ->addOrderBy('script.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countEnabled(): int
    {
        return (int) $this->createQueryBuilder('script')
            ->select('COUNT(script.id)')
            ->andWhere('script.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDisabled(): int
    {
        return (int) $this->createQueryBuilder('script')
            ->select('COUNT(script.id)')
            ->andWhere('script.enabled = :enabled')
            ->setParameter('enabled', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
