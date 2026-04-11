<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleKeyword;
use App\Entity\ArticleKeywordImportQueue;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use App\Exception\ArticleKeywordImportException;
use App\Repository\ArticleKeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleKeywordImportProcessor
{
    private const PAYLOAD_KEY = 'keywords';

    /**
     * @var list<ArticleCategoryStatus>
     */
    private const ALLOWED_IMPORT_STATUSES = [
        ArticleCategoryStatus::ACTIVE,
        ArticleCategoryStatus::INACTIVE,
    ];

    public function __construct(
        private readonly ArticleKeywordRepository $articleKeywordRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    public function process(ArticleKeywordImportQueue $queueItem): int
    {
        $payload = $this->readPayload($queueItem);
        $keywords = $this->extractKeywords($payload);
        $this->validatePayloadDuplicates($keywords);
        $processedCount = 0;

        foreach ($keywords as $index => $keywordData) {
            if (!is_array($keywordData)) {
                throw new ArticleKeywordImportException(sprintf('%s[%d] must be an array.', self::PAYLOAD_KEY, $index));
            }

            $name = $this->requireString($keywordData, 'name', $index, true);
            $language = $this->parseLanguage($keywordData['language'] ?? null, $index);
            $keyword = $this->articleKeywordRepository->findOneByLanguageAndName($language, $name) ?? new ArticleKeyword();
            $this->hydrateKeyword($keyword, $keywordData, $index);

            if (null === $keyword->getId()) {
                $this->entityManager->persist($keyword);
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(ArticleKeywordImportQueue $queueItem): array
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            throw new ArticleKeywordImportException('Import file does not exist or is outside the allowed directory.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ArticleKeywordImportException('Import file does not contain valid JSON.', 0, $exception);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function extractKeywords(array $payload): array
    {
        if (($payload['format'] ?? null) !== 'article-keyword-export') {
            throw new ArticleKeywordImportException('Unsupported import file format.');
        }

        if (($payload['version'] ?? null) !== 1) {
            throw new ArticleKeywordImportException('Unsupported import file version.');
        }

        $keywords = $payload[self::PAYLOAD_KEY] ?? null;
        if (!is_array($keywords) || [] === $keywords) {
            throw new ArticleKeywordImportException('Import file does not contain any keywords.');
        }

        return array_values($keywords);
    }

    /**
     * @param list<mixed> $keywords
     */
    private function validatePayloadDuplicates(array $keywords): void
    {
        $seenKeywords = [];

        foreach ($keywords as $index => $keywordData) {
            if (!is_array($keywordData)) {
                throw new ArticleKeywordImportException(sprintf('%s[%d] must be an array.', self::PAYLOAD_KEY, $index));
            }

            $name = $this->requireString($keywordData, 'name', $index, true);
            $language = $this->parseLanguage($keywordData['language'] ?? null, $index);
            $duplicateKey = $language->value.'|'.$name;
            if (array_key_exists($duplicateKey, $seenKeywords)) {
                throw new ArticleKeywordImportException(sprintf(
                    'Fields %s[%d].language and %s[%d].name duplicate values from %s[%d].language and %s[%d].name.',
                    self::PAYLOAD_KEY,
                    $index,
                    self::PAYLOAD_KEY,
                    $index,
                    self::PAYLOAD_KEY,
                    $seenKeywords[$duplicateKey],
                    self::PAYLOAD_KEY,
                    $seenKeywords[$duplicateKey],
                ));
            }

            $seenKeywords[$duplicateKey] = $index;
        }
    }

    /**
     * @param array<string, mixed> $keywordData
     */
    private function hydrateKeyword(ArticleKeyword $keyword, array $keywordData, int $index): void
    {
        $draftKeyword = clone $keyword;
        $draftKeyword
            ->setName($this->requireString($keywordData, 'name', $index, true))
            ->setLanguage($this->parseLanguage($keywordData['language'] ?? null, $index))
            ->setStatus($this->parseStatus($keywordData['status'] ?? null, $index))
            ->setColor($this->optionalString($keywordData, 'color'));

        $violations = $this->validator->validate($draftKeyword);
        $messages = [];

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                if ($this->shouldIgnoreViolation($violation)) {
                    continue;
                }

                $propertyPath = trim((string) $violation->getPropertyPath());
                $messages[] = sprintf(
                    '%s: %s',
                    '' !== $propertyPath ? $propertyPath : 'keyword',
                    $this->normalizeViolationMessage((string) $violation->getMessage())
                );
            }
        }

        if ([] !== $messages) {
            throw new ArticleKeywordImportException(implode(' ', $messages));
        }

        $keyword
            ->setName($draftKeyword->getName())
            ->setLanguage($draftKeyword->getLanguage())
            ->setStatus($draftKeyword->getStatus())
            ->setColor($draftKeyword->getColor());
    }

    /**
     * @param array<string, mixed> $keywordData
     */
    private function requireString(array $keywordData, string $field, int $index, bool $trim = false): string
    {
        $value = $keywordData[$field] ?? null;
        if (!is_string($value)) {
            throw new ArticleKeywordImportException(sprintf('Field %s[%d].%s is required and must be a string.', self::PAYLOAD_KEY, $index, $field));
        }

        $value = $trim ? trim($value) : $value;
        if ('' === $value) {
            throw new ArticleKeywordImportException(sprintf('Field %s[%d].%s is required.', self::PAYLOAD_KEY, $index, $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $keywordData
     */
    private function optionalString(array $keywordData, string $field): ?string
    {
        $value = $keywordData[$field] ?? null;
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new ArticleKeywordImportException(sprintf('Field %s must be a string or null.', $field));
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function parseLanguage(mixed $value, int $index): ArticleKeywordLanguage
    {
        if (!is_string($value)) {
            throw new ArticleKeywordImportException(sprintf('Field %s[%d].language must be a string.', self::PAYLOAD_KEY, $index));
        }

        $language = ArticleKeywordLanguage::tryFrom(trim($value));
        if (null === $language) {
            throw new ArticleKeywordImportException(sprintf(
                'Field %s[%d].language has unsupported value "%s". Allowed values: %s.',
                self::PAYLOAD_KEY,
                $index,
                $value,
                implode(', ', array_map(static fn (ArticleKeywordLanguage $language): string => $language->value, ArticleKeywordLanguage::cases()))
            ));
        }

        return $language;
    }

    private function parseStatus(mixed $value, int $index): ArticleCategoryStatus
    {
        if (!is_string($value)) {
            throw new ArticleKeywordImportException(sprintf('Field %s[%d].status must be a string.', self::PAYLOAD_KEY, $index));
        }

        $status = ArticleCategoryStatus::tryFrom(trim($value));
        if (null === $status || !in_array($status, self::ALLOWED_IMPORT_STATUSES, true)) {
            throw new ArticleKeywordImportException(sprintf(
                'Field %s[%d].status has unsupported value "%s". Allowed values: %s.',
                self::PAYLOAD_KEY,
                $index,
                $value,
                implode(', ', array_map(static fn (ArticleCategoryStatus $status): string => $status->value, self::ALLOWED_IMPORT_STATUSES))
            ));
        }

        return $status;
    }

    private function shouldIgnoreViolation(ConstraintViolationInterface $violation): bool
    {
        return UniqueEntity::NOT_UNIQUE_ERROR === $violation->getCode();
    }

    private function normalizeViolationMessage(string $message): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $message));
    }
}
