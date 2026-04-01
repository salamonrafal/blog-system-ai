<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleCategory;
use App\Entity\CategoryImportQueue;
use App\Enum\ArticleCategoryStatus;
use App\Exception\CategoryImportException;
use App\Repository\ArticleCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryImportProcessor
{
    /**
     * @var list<ArticleCategoryStatus>
     */
    private const ALLOWED_IMPORT_STATUSES = [
        ArticleCategoryStatus::ACTIVE,
        ArticleCategoryStatus::INACTIVE,
    ];
    /**
     * @var list<string>
     */
    private const UNIQUE_NAME_MESSAGES = [
        'This category name is already taken.',
    ];

    /**
     * @var list<string>
     */
    private const UNIQUE_SLUG_MESSAGES = [
        'This category slug is already taken.',
    ];

    public function __construct(
        private readonly ArticleCategoryRepository $articleCategoryRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    public function process(CategoryImportQueue $queueItem): int
    {
        $payload = $this->readPayload($queueItem);
        $categories = $this->extractCategories($payload);
        $processedCount = 0;

        foreach ($categories as $index => $categoryData) {
            if (!is_array($categoryData)) {
                throw new CategoryImportException(sprintf('Category item[%d] must be an array.', $index));
            }

            $slug = $this->requireString($categoryData, 'slug', $index, true);
            $category = $this->articleCategoryRepository->findOneBy(['slug' => $slug]) ?? new ArticleCategory();
            $this->hydrateCategory($category, $categoryData, $index);

            if (null === $category->getId()) {
                $this->entityManager->persist($category);
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(CategoryImportQueue $queueItem): array
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            throw new CategoryImportException('Import file does not exist or is outside the allowed directory.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new CategoryImportException('Import file does not contain valid JSON.', 0, $exception);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function extractCategories(array $payload): array
    {
        if (($payload['format'] ?? null) !== 'category-export') {
            throw new CategoryImportException('Unsupported import file format.');
        }

        if (($payload['version'] ?? null) !== 1) {
            throw new CategoryImportException('Unsupported import file version.');
        }

        $categories = $payload['categories'] ?? $payload['category'] ?? null;
        if (!is_array($categories) || [] === $categories) {
            throw new CategoryImportException('Import file does not contain any categories.');
        }

        return array_values($categories);
    }

    /**
     * @param array<string, mixed> $categoryData
     */
    private function hydrateCategory(ArticleCategory $category, array $categoryData, int $index): void
    {
        $draftCategory = clone $category;
        $this->applyCategoryData($draftCategory, $categoryData, $index);

        $messages = $this->validateUniqueFields($draftCategory);
        $violations = $this->validator->validate($draftCategory);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                if ($this->shouldIgnoreViolation($violation)) {
                    continue;
                }

                $propertyPath = trim((string) $violation->getPropertyPath());
                $messages[] = sprintf(
                    '%s: %s',
                    '' !== $propertyPath ? $propertyPath : 'category',
                    (string) $violation->getMessage()
                );
            }

        }

        if ([] !== $messages) {
            throw new CategoryImportException(implode(' ', $messages));
        }

        $this->copyCategoryData($draftCategory, $category);
    }

    /**
     * @param array<string, mixed> $categoryData
     */
    private function applyCategoryData(ArticleCategory $category, array $categoryData, int $index): void
    {
        $category
            ->setName($this->requireString($categoryData, 'name', $index, true))
            ->setSlug($this->requireString($categoryData, 'slug', $index, true))
            ->setShortDescription($this->optionalString($categoryData, 'short_description'))
            ->setTitles($this->parseTranslations($categoryData['titles'] ?? null, 'titles', $index))
            ->setDescriptions($this->parseTranslations($categoryData['descriptions'] ?? null, 'descriptions', $index))
            ->setIcon($this->optionalString($categoryData, 'icon'))
            ->setStatus($this->parseStatus($categoryData['status'] ?? null, $index));
    }

    private function copyCategoryData(ArticleCategory $source, ArticleCategory $target): void
    {
        $target
            ->setName($source->getName())
            ->setSlug($source->getSlug())
            ->setShortDescription($source->getShortDescription())
            ->setTitles($source->getTitles())
            ->setDescriptions($source->getDescriptions())
            ->setIcon($source->getIcon())
            ->setStatus($source->getStatus());
    }

    /**
     * @return list<string>
     */
    private function validateUniqueFields(ArticleCategory $category): array
    {
        $messages = [];
        $ignoreId = $category->getId();

        if ($this->articleCategoryRepository->nameExists($category->getName(), $ignoreId)) {
            $messages[] = 'name: This category name is already taken.';
        }

        if ($this->articleCategoryRepository->slugExists($category->getSlug(), $ignoreId)) {
            $messages[] = 'slug: This category slug is already taken.';
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $categoryData
     */
    private function requireString(array $categoryData, string $field, int $index, bool $trim = false): string
    {
        $value = $categoryData[$field] ?? null;
        if (!is_string($value)) {
            throw new CategoryImportException(sprintf('Field category item[%d].%s is required and must be a string.', $index, $field));
        }

        $value = $trim ? trim($value) : $value;
        if ('' === $value) {
            throw new CategoryImportException(sprintf('Field category item[%d].%s is required.', $index, $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $categoryData
     */
    private function optionalString(array $categoryData, string $field): ?string
    {
        $value = $categoryData[$field] ?? null;
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new CategoryImportException(sprintf('Field %s must be a string or null.', $field));
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    /**
     * @return array<string, string>
     */
    private function parseTranslations(mixed $value, string $field, int $index): array
    {
        if (!is_array($value)) {
            throw new CategoryImportException(sprintf('Field category item[%d].%s must be a translation map.', $index, $field));
        }

        $translations = [];

        foreach ($value as $language => $translation) {
            if (!is_string($language) || '' === trim($language)) {
                throw new CategoryImportException(sprintf('Field category item[%d].%s contains an invalid language key.', $index, $field));
            }

            if (!is_string($translation)) {
                throw new CategoryImportException(sprintf('Field category item[%d].%s.%s must be a string.', $index, $field, $language));
            }

            $translations[strtolower(trim($language))] = trim($translation);
        }

        return $translations;
    }

    private function parseStatus(mixed $value, int $index): ArticleCategoryStatus
    {
        if (!is_string($value)) {
            throw new CategoryImportException(sprintf('Field category item[%d].status must be a string.', $index));
        }

        $normalizedValue = trim($value);
        $status = ArticleCategoryStatus::tryFrom($normalizedValue);
        if (null === $status || !in_array($status, self::ALLOWED_IMPORT_STATUSES, true)) {
            throw new CategoryImportException(sprintf(
                'Field category item[%d].status has unsupported value "%s". Allowed values: %s',
                $index,
                $value,
                implode(', ', array_map(static fn (ArticleCategoryStatus $status): string => $status->value, self::ALLOWED_IMPORT_STATUSES))
            ));
        }

        return $status;
    }

    private function shouldIgnoreViolation(ConstraintViolationInterface $violation): bool
    {
        return in_array(
            trim((string) $violation->getMessage()),
            [...self::UNIQUE_NAME_MESSAGES, ...self::UNIQUE_SLUG_MESSAGES],
            true
        );
    }
}
