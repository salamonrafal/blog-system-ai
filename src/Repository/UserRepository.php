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
            ->select('user.id AS id')
            ->addSelect('user.email AS HIDDEN authorEmail')
            ->innerJoin(Article::class, 'article', 'WITH', 'article.createdBy = user')
            ->addSelect('MAX(article.updatedAt) AS HIDDEN latestArticleUpdate')
            ->groupBy('user.id')
            ->addGroupBy('user.email')
            ->orderBy('latestArticleUpdate', 'DESC')
            ->addOrderBy('authorEmail', 'ASC')
            ->setMaxResults($limit);

        $query = self::normalizeAuthorSearchText($query);
        if ('' !== $query) {
            $queryBuilder
                ->andWhere("LOWER(user.email) LIKE :authorQuery ESCAPE '!' OR user.fullNameSearch LIKE :authorQuery ESCAPE '!' OR user.nicknameSearch LIKE :authorQuery ESCAPE '!'")
                ->setParameter('authorQuery', '%'.self::escapeLikePattern($query).'%');
        }

        /** @var list<array{id: int|string}> $authorIdRows */
        $authorIdRows = $queryBuilder
            ->getQuery()
            ->getScalarResult();

        $authorIds = array_map(static fn (array $row): int => (int) $row['id'], $authorIdRows);

        if ([] === $authorIds) {
            return [];
        }

        /** @var list<User> $users */
        $users = $this->createQueryBuilder('user')
            ->andWhere('user.id IN (:authorIds)')
            ->setParameter('authorIds', $authorIds)
            ->getQuery()
            ->getResult();

        $usersById = [];
        foreach ($users as $user) {
            $userId = $user->getId();
            if (null !== $userId) {
                $usersById[$userId] = $user;
            }
        }

        return array_values(array_filter(
            array_map(static fn (int $authorId): ?User => $usersById[$authorId] ?? null, $authorIds),
        ));
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

    private static function normalizeAuthorSearchText(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    private static function escapeLikePattern(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
