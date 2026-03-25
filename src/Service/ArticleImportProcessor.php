<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleImportQueue;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Exception\ArticleImportException;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleImportProcessor
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly ArticlePublisher $articlePublisher,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    public function process(ArticleImportQueue $queueItem): int
    {
        $payload = $this->readPayload($queueItem);
        $articles = $this->extractArticles($payload);
        $processedCount = 0;

        foreach ($articles as $index => $articleData) {
            if (!is_array($articleData)) {
                throw new ArticleImportException(sprintf('Element article[%d] musi być obiektem JSON.', $index));
            }

            $slug = $this->requireString($articleData, 'slug', $index, true);
            $article = $this->articleRepository->findOneBySlug($slug) ?? new Article();
            $this->hydrateArticle($article, $articleData, $index);

            if (null === $article->getId()) {
                $this->entityManager->persist($article);
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(ArticleImportQueue $queueItem): array
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            throw new ArticleImportException('Plik importu nie istnieje albo jest poza dozwolonym katalogiem.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ArticleImportException('Plik importu nie zawiera poprawnego JSON.', 0, $exception);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function extractArticles(array $payload): array
    {
        if (($payload['format'] ?? null) !== 'article-export') {
            throw new ArticleImportException('Nieobsługiwany format pliku importu.');
        }

        if (($payload['version'] ?? null) !== 1) {
            throw new ArticleImportException('Nieobsługiwana wersja pliku importu.');
        }

        $articles = $payload['article'] ?? null;
        if (!is_array($articles) || [] === $articles) {
            throw new ArticleImportException('Plik importu nie zawiera żadnych artykułów.');
        }

        if (1 !== count($articles)) {
            throw new ArticleImportException('Plik importu musi zawierać dokładnie jeden artykuł.');
        }

        return array_values($articles);
    }

    /**
     * @param array<string, mixed> $articleData
     */
    private function hydrateArticle(Article $article, array $articleData, int $index): void
    {
        $draftArticle = clone $article;
        $title = $this->requireString($articleData, 'title', $index, true);
        $language = $this->parseLanguage($articleData['language'] ?? null, $index);
        $status = $this->parseStatus($articleData['status'] ?? null, $index);
        $content = $this->requireString($articleData, 'content', $index, true);
        $slug = $this->requireString($articleData, 'slug', $index, true);
        $excerpt = $this->optionalString($articleData, 'excerpt');
        $headlineImage = $this->optionalString($articleData, 'headline_image');
        $headlineImageEnabled = $this->parseBoolean($articleData['headline_image_enabled'] ?? null, 'headline_image_enabled', $index);
        $publishedAt = $this->parseNullableDateTime($articleData['published_at'] ?? null, 'published_at', $index);

        $draftArticle
            ->setTitle($title)
            ->setLanguage($language)
            ->setSlug($slug)
            ->setExcerpt($excerpt)
            ->setHeadlineImage($headlineImage)
            ->setHeadlineImageEnabled($headlineImageEnabled)
            ->setContent($content)
            ->setStatus($status)
            ->setPublishedAt($publishedAt);

        $this->articlePublisher->prepareForSave($draftArticle);

        $violations = $this->validator->validate($draftArticle);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $propertyPath = trim((string) $violation->getPropertyPath());
                $messages[] = sprintf(
                    '%s: %s',
                    '' !== $propertyPath ? $propertyPath : 'article',
                    $this->normalizeViolationMessage((string) $violation->getMessage())
                );
            }

            throw new ArticleImportException(implode(' ', $messages));
        }

        $article
            ->setTitle($draftArticle->getTitle())
            ->setLanguage($draftArticle->getLanguage())
            ->setSlug($draftArticle->getSlug())
            ->setExcerpt($draftArticle->getExcerpt())
            ->setHeadlineImage($draftArticle->getHeadlineImage())
            ->setHeadlineImageEnabled($draftArticle->isHeadlineImageEnabled())
            ->setContent($draftArticle->getContent())
            ->setStatus($draftArticle->getStatus())
            ->setPublishedAt($draftArticle->getPublishedAt());
    }
    /**
     * @param array<string, mixed> $articleData
     */
    private function requireString(array $articleData, string $field, int $index, bool $trim = false): string
    {
        $value = $articleData[$field] ?? null;
        if (!is_string($value)) {
            throw new ArticleImportException(sprintf('Pole article[%d].%s jest wymagane i musi być tekstem.', $index, $field));
        }

        $value = $trim ? trim($value) : $value;
        if ('' === $value) {
            throw new ArticleImportException(sprintf('Pole article[%d].%s jest wymagane.', $index, $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $articleData
     */
    private function optionalString(array $articleData, string $field): ?string
    {
        $value = $articleData[$field] ?? null;
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new ArticleImportException(sprintf('Pole %s musi być tekstem albo null.', $field));
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function parseLanguage(mixed $value, int $index): ArticleLanguage
    {
        if (!is_string($value)) {
            throw new ArticleImportException(sprintf('Pole article[%d].language musi być tekstem.', $index));
        }

        $language = ArticleLanguage::tryFrom(trim($value));
        if (null === $language) {
            throw new ArticleImportException(sprintf(
                'Pole article[%d].language ma nieobsługiwaną wartość "%s". Dozwolone wartości: %s.',
                $index,
                $value,
                implode(', ', array_map(static fn (ArticleLanguage $language): string => $language->value, ArticleLanguage::cases()))
            ));
        }

        return $language;
    }

    private function parseStatus(mixed $value, int $index): ArticleStatus
    {
        if (!is_string($value)) {
            throw new ArticleImportException(sprintf('Pole article[%d].status musi być tekstem.', $index));
        }

        $status = ArticleStatus::tryFrom(trim($value));
        if (null === $status) {
            throw new ArticleImportException(sprintf(
                'Pole article[%d].status ma nieobsługiwaną wartość "%s". Dozwolone wartości: %s.',
                $index,
                $value,
                implode(', ', array_map(static fn (ArticleStatus $status): string => $status->value, ArticleStatus::cases()))
            ));
        }

        return $status;
    }

    private function parseBoolean(mixed $value, string $field, int $index): bool
    {
        if (!is_bool($value)) {
            throw new ArticleImportException(sprintf('Pole article[%d].%s musi mieć wartość true albo false.', $index, $field));
        }

        return $value;
    }

    private function parseNullableDateTime(mixed $value, string $field, int $index): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new ArticleImportException(sprintf('Pole article[%d].%s musi być tekstem ISO-8601 albo null.', $index, $field));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new ArticleImportException(sprintf('Pole article[%d].%s nie zawiera poprawnej daty.', $index, $field), 0, $exception);
        }
    }

    private function normalizeViolationMessage(string $message): string
    {
        return match ($message) {
            'validation_article_title_required' => 'to pole jest wymagane.',
            'validation_article_title_too_long' => 'maksymalna długość to 255 znaków.',
            'validation_article_slug_too_long' => 'maksymalna długość to 255 znaków.',
            'validation_article_excerpt_too_long' => 'maksymalna długość to 320 znaków.',
            'validation_article_headline_image_too_long' => 'maksymalna długość to 500 znaków.',
            'validation_article_headline_image_invalid' => 'musi zaczynać się od http://, https:// albo /.',
            'validation_article_content_required' => 'to pole jest wymagane.',
            default => $message,
        };
    }
}
