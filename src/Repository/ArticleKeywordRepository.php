<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleKeyword;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use App\Enum\ArticleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleKeyword>
 */
class ArticleKeywordRepository extends ServiceEntityRepository
{
    private const FIND_BY_LANGUAGE_AND_NAME_PAIRS_CHUNK_SIZE = 400;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleKeyword::class);
    }

    /**
     * @return list<ArticleKeyword>
     */
    public function findForAdminIndex(): array
    {
        /** @var list<ArticleKeyword> $keywords */
        $keywords = $this->createQueryBuilder('keyword')
            ->orderBy('keyword.language', 'ASC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    /**
     * @return list<ArticleKeyword>
     */
    public function findForExport(): array
    {
        /** @var list<ArticleKeyword> $keywords */
        $keywords = $this->createQueryBuilder('keyword')
            ->leftJoin('keyword.articles', 'article')
            ->addSelect('article')
            ->orderBy('keyword.language', 'ASC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    /**
     * @return list<ArticleKeyword>
     */
    public function findForArticleAssignment(): array
    {
        /** @var list<ArticleKeyword> $keywords */
        $keywords = $this->createQueryBuilder('keyword')
            ->orderBy('keyword.status', 'ASC')
            ->addOrderBy('keyword.language', 'ASC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    public function countActive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::ACTIVE]);
    }

    public function countInactive(): int
    {
        return $this->count(['status' => ArticleCategoryStatus::INACTIVE]);
    }

    public function nameExistsForLanguage(string $name, string $language, ?int $ignoreId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('keyword')
            ->select('COUNT(keyword.id)')
            ->andWhere('keyword.name = :name')
            ->andWhere('keyword.language = :language')
            ->setParameter('name', $name)
            ->setParameter('language', strtolower(trim($language)));

        if (null !== $ignoreId) {
            $queryBuilder
                ->andWhere('keyword.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    public function findOneByLanguageAndName(ArticleKeywordLanguage $language, string $name): ?ArticleKeyword
    {
        return $this->createQueryBuilder('keyword')
            ->andWhere('keyword.language = :language')
            ->andWhere('keyword.name = :name')
            ->setParameter('language', $language)
            ->setParameter('name', trim($name))
            ->orderBy('keyword.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<array{language: ArticleKeywordLanguage, name: string}> $pairs
     *
     * @return list<ArticleKeyword>
     */
    public function findByLanguageAndNamePairs(array $pairs): array
    {
        if ([] === $pairs) {
            return [];
        }

        $keywordsById = [];

        foreach (array_chunk(array_values($pairs), self::FIND_BY_LANGUAGE_AND_NAME_PAIRS_CHUNK_SIZE) as $chunk) {
            foreach ($this->findByLanguageAndNamePairsChunk($chunk) as $keyword) {
                $keywordId = $keyword->getId();
                if (null === $keywordId) {
                    continue;
                }

                $keywordsById[$keywordId] = $keyword;
            }
        }

        ksort($keywordsById);

        return array_values($keywordsById);
    }

    public function findOneActiveByLanguageAndName(ArticleKeywordLanguage $language, string $name): ?ArticleKeyword
    {
        return $this->createQueryBuilder('keyword')
            ->andWhere('keyword.language = :language')
            ->andWhere('keyword.name = :name')
            ->andWhere('keyword.status = :status')
            ->setParameter('language', $language)
            ->setParameter('name', trim($name))
            ->setParameter('status', ArticleCategoryStatus::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<array{language: ArticleKeywordLanguage, name: string}> $pairs
     *
     * @return list<ArticleKeyword>
     */
    private function findByLanguageAndNamePairsChunk(array $pairs): array
    {
        $queryBuilder = $this->createQueryBuilder('keyword');
        $orExpression = $queryBuilder->expr()->orX();

        foreach (array_values($pairs) as $index => $pair) {
            $languageParameter = 'language_'.$index;
            $nameParameter = 'name_'.$index;

            $orExpression->add(sprintf(
                '(keyword.language = :%s AND keyword.name = :%s)',
                $languageParameter,
                $nameParameter,
            ));

            $queryBuilder
                ->setParameter($languageParameter, $pair['language'])
                ->setParameter($nameParameter, trim($pair['name']));
        }

        /** @var list<ArticleKeyword> $keywords */
        $keywords = $queryBuilder
            ->andWhere($orExpression)
            ->orderBy('keyword.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $keywords;
    }

    /**
     * @return list<array{keyword: ArticleKeyword, article_count: int}>
     */
    public function findTopUsedInPublishedArticles(int $limit = 5): array
    {
        $limit = max(1, $limit);

        /** @var list<array{0: ArticleKeyword, articleCount: string|int}> $rows */
        $rows = $this->createQueryBuilder('keyword')
            ->select('keyword, COUNT(DISTINCT article.id) AS articleCount')
            ->innerJoin('keyword.articles', 'article')
            ->andWhere('keyword.status = :keywordStatus')
            ->andWhere('article.status = :articleStatus')
            ->andWhere('(keyword.language = :allLanguage OR keyword.language = article.language)')
            ->setParameter('keywordStatus', ArticleCategoryStatus::ACTIVE)
            ->setParameter('articleStatus', ArticleStatus::PUBLISHED)
            ->setParameter('allLanguage', ArticleKeywordLanguage::ALL)
            ->groupBy('keyword.id')
            ->orderBy('articleCount', 'DESC')
            ->addOrderBy('keyword.name', 'ASC')
            ->addOrderBy('keyword.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row): array => [
                'keyword' => $row[0],
                'article_count' => (int) $row['articleCount'],
            ],
            $rows,
        );
    }
}
