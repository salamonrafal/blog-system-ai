<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private EntityManager $entityManager;
    private UserRepository $repository;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = new UserRepository($registry);
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }

    public function testFindForArticleAuthorFilterExecutesUnicodeSearchWithOrderingAndUniqueAuthors(): void
    {
        $latestAuthor = $this->createUser('lukasz@example.com', 'Łukasz Nowak');
        $secondAuthor = $this->createUser('second@example.com', 'Łukasz Nowak');
        $nonAuthor = $this->createUser('non-author@example.com', 'Łukasz Bez Artykułu');
        $this->entityManager->persist($nonAuthor);

        $latestArticle = $this->createArticle('Latest article', 'latest-article', $latestAuthor);
        $olderArticle = $this->createArticle('Older article', 'older-article', $latestAuthor);
        $secondArticle = $this->createArticle('Second article', 'second-article', $secondAuthor);

        $this->flushAndSetArticleUpdateTimes([
            [$latestArticle, '2026-01-10 12:00:00'],
            [$olderArticle, '2026-01-01 12:00:00'],
            [$secondArticle, '2026-01-09 12:00:00'],
        ]);

        $authors = $this->repository->findForArticleAuthorFilter(' łukasz ', 10);

        $this->assertCount(2, $authors);
        $this->assertSame(['lukasz@example.com', 'second@example.com'], array_map(
            static fn (User $user): string => $user->getEmail(),
            $authors,
        ));
    }

    public function testFindForArticleAuthorFilterAppliesLimitInQueryResults(): void
    {
        $firstAuthor = $this->createUser('first@example.com', 'Author One');
        $secondAuthor = $this->createUser('second@example.com', 'Author Two');
        $thirdAuthor = $this->createUser('third@example.com', 'Author Three');

        $firstArticle = $this->createArticle('First article', 'first-article', $firstAuthor);
        $secondArticle = $this->createArticle('Second article', 'second-article', $secondAuthor);
        $thirdArticle = $this->createArticle('Third article', 'third-article', $thirdAuthor);

        $this->flushAndSetArticleUpdateTimes([
            [$firstArticle, '2026-01-10 12:00:00'],
            [$secondArticle, '2026-01-09 12:00:00'],
            [$thirdArticle, '2026-01-08 12:00:00'],
        ]);

        $authors = $this->repository->findForArticleAuthorFilter('author', 2);

        $this->assertCount(2, $authors);
        $this->assertSame(['first@example.com', 'second@example.com'], array_map(
            static fn (User $user): string => $user->getEmail(),
            $authors,
        ));
    }

    public function testFindForArticleAuthorFilterMatchesEmailCaseInsensitively(): void
    {
        $author = $this->createUser('author@example.com', 'Writer');
        $this->createArticle('Author article', 'author-article', $author);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $authors = $this->repository->findForArticleAuthorFilter('Author', 10);

        $this->assertCount(1, $authors);
        $this->assertSame('author@example.com', $authors[0]->getEmail());
    }

    public function testFindForArticleAuthorFilterTreatsLikeWildcardsAsLiteralText(): void
    {
        $wildcardAuthor = $this->createUser('literal-percent@example.com', 'Literal 100% Match');
        $underscoreAuthor = $this->createUser('literal-underscore@example.com', 'Literal under_score');
        $plainAuthor = $this->createUser('plain@example.com', 'Plain Author');

        $this->createArticle('Wildcard article', 'wildcard-article', $wildcardAuthor);
        $this->createArticle('Underscore article', 'underscore-article', $underscoreAuthor);
        $this->createArticle('Plain article', 'plain-article', $plainAuthor);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $percentAuthors = $this->repository->findForArticleAuthorFilter('%', 10);
        $underscoreAuthors = $this->repository->findForArticleAuthorFilter('_', 10);

        $this->assertSame(['literal-percent@example.com'], array_map(
            static fn (User $user): string => $user->getEmail(),
            $percentAuthors,
        ));
        $this->assertSame(['literal-underscore@example.com'], array_map(
            static fn (User $user): string => $user->getEmail(),
            $underscoreAuthors,
        ));
    }

    public function testFindArticleAuthorByIdOnlyReturnsUsersWhoAuthoredArticles(): void
    {
        $author = $this->createUser('author@example.com', 'Author');
        $nonAuthor = $this->createUser('non-author@example.com', 'Non Author');
        $this->entityManager->persist($nonAuthor);
        $this->createArticle('Author article', 'author-article', $author);
        $this->entityManager->flush();
        $authorId = $author->getId();
        $nonAuthorId = $nonAuthor->getId();
        $this->entityManager->clear();

        $this->assertSame('author@example.com', $this->repository->findArticleAuthorById($authorId)?->getEmail());
        $this->assertNull($this->repository->findArticleAuthorById($nonAuthorId));
    }

    private function createUser(string $email, string $fullName): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFullName($fullName)
            ->setPassword('password');
        $this->entityManager->persist($user);

        return $user;
    }

    private function createArticle(string $title, string $slug, User $author): Article
    {
        $article = (new Article())
            ->setTitle($title)
            ->setSlug($slug)
            ->setContent('Content')
            ->setCreatedBy($author);
        $this->entityManager->persist($article);

        return $article;
    }

    /**
     * @param list<array{0: Article, 1: string}> $updateTimesByArticle
     */
    private function flushAndSetArticleUpdateTimes(array $updateTimesByArticle): void
    {
        $this->entityManager->flush();

        foreach ($updateTimesByArticle as [$article, $updatedAt]) {
            $articleId = $article->getId();
            self::assertIsInt($articleId);
            $this->entityManager->getConnection()->update('article', [
                'updated_at' => $updatedAt,
            ], [
                'id' => $articleId,
            ]);
        }

        $this->entityManager->clear();
    }
}
