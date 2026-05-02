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
            ->addOrderBy('user.email', 'ASC');

        $query = self::normalizeAuthorSearchText($query);
        if ('' === $query) {
            $queryBuilder->setMaxResults($limit);
        }

        /** @var list<User> $users */
        $users = $queryBuilder
            ->getQuery()
            ->getResult();

        if ('' === $query) {
            return $users;
        }

        return \array_slice(
            \array_values(\array_filter(
                $users,
                static fn (User $user): bool => self::matchesArticleAuthorFilterQuery($user, $query),
            )),
            0,
            $limit,
        );
    }

    public function findArticleAuthorById(int $id): ?User
    {
        /** @var ?User $user */
        $user = $this->createQueryBuilder('user')
            ->innerJoin(Article::class, 'article', 'WITH', 'article.createdBy = user')
            ->andWhere('user.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
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

    private static function matchesArticleAuthorFilterQuery(User $user, string $query): bool
    {
        foreach ([$user->getEmail(), $user->getFullName(), $user->getNickname()] as $value) {
            if (null !== $value && str_contains(self::normalizeAuthorSearchText($value), $query)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeAuthorSearchText(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
