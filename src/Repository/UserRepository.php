<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => strtolower(trim($email))]);
    }

    /**
     * @return list<User>
     */
    public function findForAdminIndex(): array
    {
        /** @var list<User> $users */
        $users = $this->createQueryBuilder('user')
            ->orderBy('user.createdAt', 'DESC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->getResult();

        return $users;
    }

    /**
     * @return list<User>
     */
    public function findForArticleAuthorFilter(string $query = '', int $limit = 10): array
    {
        if ($limit <= 0) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('user')
            ->innerJoin(Article::class, 'article', 'WITH', 'article.createdBy = user')
            ->addSelect('MAX(article.updatedAt) AS HIDDEN latestArticleUpdate')
            ->groupBy('user.id')
            ->orderBy('latestArticleUpdate', 'DESC')
            ->addOrderBy('user.email', 'ASC')
            ->setMaxResults($limit);

        $query = strtolower(trim($query));
        if ('' !== $query) {
            $queryBuilder
                ->andWhere('LOWER(user.email) LIKE :authorQuery OR LOWER(user.fullName) LIKE :authorQuery OR LOWER(user.nickname) LIKE :authorQuery')
                ->setParameter('authorQuery', '%'.$query.'%');
        }

        /** @var list<User> $users */
        $users = $queryBuilder
            ->getQuery()
            ->getResult();

        return $users;
    }

    public function countActive(): int
    {
        return $this->count(['isActive' => true]);
    }

    public function countInactive(): int
    {
        return $this->count(['isActive' => false]);
    }

    public function countAdministrators(): int
    {
        return (int) $this->createQueryBuilder('user')
            ->select('COUNT(user.id)')
            ->where('user.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFirstAdministrator(): ?User
    {
        /** @var ?User $user */
        $user = $this->createQueryBuilder('user')
            ->where('user.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->orderBy('user.createdAt', 'ASC')
            ->addOrderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }
}
