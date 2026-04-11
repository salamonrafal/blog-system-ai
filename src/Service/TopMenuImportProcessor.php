<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuImportQueue;
use App\Entity\TopMenuItem;
use App\Enum\TopMenuItemStatus;
use App\Enum\TopMenuItemTargetType;
use App\Exception\TopMenuImportException;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleRepository;
use App\Repository\TopMenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TopMenuImportProcessor
{
    public function __construct(
        private readonly TopMenuItemRepository $topMenuItemRepository,
        private readonly ArticleCategoryRepository $articleCategoryRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagedFilePathResolver $managedFilePathResolver,
    ) {
    }

    public function process(TopMenuImportQueue $queueItem): int
    {
        $payload = $this->readPayload($queueItem);
        $items = $this->extractItems($payload);
        $itemsByUniqueName = $this->indexByUniqueName($items);
        $existingItems = $this->topMenuItemRepository->findByUniqueNames(array_keys($itemsByUniqueName));
        $existingParentItems = $this->fetchExistingParentItems($itemsByUniqueName, $existingItems);
        $sortedItems = $this->sortByHierarchy($itemsByUniqueName, $existingParentItems);
        $existingItems += $existingParentItems;
        $processedCount = 0;

        foreach ($sortedItems as $itemData) {
            $uniqueName = (string) $itemData['unique_name'];
            $menuItem = $existingItems[$uniqueName] ?? new TopMenuItem();
            $sourceIndex = isset($itemData['__source_index']) && is_int($itemData['__source_index']) ? $itemData['__source_index'] : 0;

            $this->hydrateMenuItem($menuItem, $itemData, $sourceIndex, $existingItems);

            if (null === $menuItem->getId()) {
                $this->entityManager->persist($menuItem);
            }

            $existingItems[$uniqueName] = $menuItem;
            ++$processedCount;
        }

        return $processedCount;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(TopMenuImportQueue $queueItem): array
    {
        $absolutePath = $this->managedFilePathResolver->resolveImportPath($queueItem->getFilePath());
        if (null === $absolutePath || !is_file($absolutePath)) {
            throw new TopMenuImportException('Import file does not exist or is outside the allowed directory.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TopMenuImportException('Import file does not contain valid JSON.', 0, $exception);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function extractItems(array $payload): array
    {
        if (($payload['format'] ?? null) !== 'top-menu-export') {
            throw new TopMenuImportException('Unsupported import file format.');
        }

        if (($payload['version'] ?? null) !== 1) {
            throw new TopMenuImportException('Unsupported import file version.');
        }

        $items = $payload['menu_items'] ?? null;
        if (!is_array($items) || [] === $items) {
            throw new TopMenuImportException('Import file does not contain any menu items.');
        }

        $normalizedItems = [];

        foreach (array_values($items) as $index => $itemData) {
            if (!is_array($itemData)) {
                throw new TopMenuImportException(sprintf('Element menu_items[%d] must be an array.', $index));
            }

            $itemData['__source_index'] = $index;
            $normalizedItems[] = $itemData;
        }

        return $normalizedItems;
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, array<string, mixed>>
     */
    private function indexByUniqueName(array $items): array
    {
        $indexedItems = [];

        foreach ($items as $index => $itemData) {
            $uniqueName = $this->requireString($itemData, 'unique_name', $index, true, 'menu_items');

            if (isset($indexedItems[$uniqueName])) {
                throw new TopMenuImportException(sprintf('Field menu_items[%d].unique_name must be unique in the import file.', $index));
            }

            $indexedItems[$uniqueName] = $itemData;
        }

        return $indexedItems;
    }

    /**
     * @param array<string, array<string, mixed>> $itemsByUniqueName
     * @param array<string, TopMenuItem> $existingParentItems
     *
     * @return list<array<string, mixed>>
     */
    private function sortByHierarchy(array $itemsByUniqueName, array $existingParentItems): array
    {
        $depths = [];
        $visiting = [];

        $resolveDepth = function (string $uniqueName) use (&$resolveDepth, &$depths, &$visiting, $itemsByUniqueName, $existingParentItems): int {
            if (isset($depths[$uniqueName])) {
                return $depths[$uniqueName];
            }

            if (isset($visiting[$uniqueName])) {
                throw new TopMenuImportException(sprintf('Detected a cyclic parent-child relation for menu item "%s".', $uniqueName));
            }

            $visiting[$uniqueName] = true;
            $parentUniqueName = $itemsByUniqueName[$uniqueName]['parent_unique_name'] ?? null;

            if (null !== $parentUniqueName && !is_string($parentUniqueName)) {
                throw new TopMenuImportException(sprintf('Field menu_items[%d].parent_unique_name must be a string or null.', $itemsByUniqueName[$uniqueName]['__source_index'] ?? 0));
            }

            if (null === $parentUniqueName || '' === trim($parentUniqueName)) {
                $depths[$uniqueName] = 0;
            } else {
                $parentUniqueName = trim($parentUniqueName);

                if (!isset($itemsByUniqueName[$parentUniqueName]) && !isset($existingParentItems[$parentUniqueName])) {
                    throw new TopMenuImportException(sprintf(
                        'Parent "%s" was not found for menu item "%s".',
                        $parentUniqueName,
                        $uniqueName
                    ));
                }

                $depths[$uniqueName] = isset($itemsByUniqueName[$parentUniqueName])
                    ? $resolveDepth($parentUniqueName) + 1
                    : 1;
            }

            unset($visiting[$uniqueName]);

            return $depths[$uniqueName];
        };

        $sortable = [];
        foreach ($itemsByUniqueName as $uniqueName => $itemData) {
            $sortable[] = [
                'depth' => $resolveDepth($uniqueName),
                'position' => isset($itemData['position']) && is_numeric($itemData['position']) ? (int) $itemData['position'] : 0,
                'unique_name' => $uniqueName,
                'item' => $itemData,
            ];
        }

        usort(
            $sortable,
            static function (array $left, array $right): int {
                $depthOrder = $left['depth'] <=> $right['depth'];
                if (0 !== $depthOrder) {
                    return $depthOrder;
                }

                $positionOrder = $left['position'] <=> $right['position'];
                if (0 !== $positionOrder) {
                    return $positionOrder;
                }

                return $left['unique_name'] <=> $right['unique_name'];
            }
        );

        return array_values(array_map(static fn (array $row): array => $row['item'], $sortable));
    }

    /**
     * @param array<string, array<string, mixed>> $itemsByUniqueName
     * @param array<string, TopMenuItem> $existingItems
     *
     * @return array<string, TopMenuItem>
     */
    private function fetchExistingParentItems(array $itemsByUniqueName, array $existingItems): array
    {
        $missingParentUniqueNames = [];

        foreach ($itemsByUniqueName as $itemData) {
            $parentUniqueName = $itemData['parent_unique_name'] ?? null;
            if (!is_string($parentUniqueName)) {
                continue;
            }

            $parentUniqueName = trim($parentUniqueName);
            if ('' === $parentUniqueName) {
                continue;
            }

            if (isset($itemsByUniqueName[$parentUniqueName]) || isset($existingItems[$parentUniqueName])) {
                continue;
            }

            $missingParentUniqueNames[$parentUniqueName] = true;
        }

        if ([] === $missingParentUniqueNames) {
            return [];
        }

        return $this->topMenuItemRepository->findByUniqueNames(array_keys($missingParentUniqueNames));
    }

    /**
     * @param array<string, mixed> $itemData
     * @param array<string, TopMenuItem> $existingItems
     */
    private function hydrateMenuItem(TopMenuItem $menuItem, array $itemData, int $index, array $existingItems): void
    {
        $draftMenuItem = clone $menuItem;
        $labels = $this->requireLabels($itemData, $index);
        $uniqueName = $this->requireString($itemData, 'unique_name', $index, true, 'menu_items');
        $targetType = $this->parseTargetType($itemData['target_type'] ?? null, $index);
        $status = $this->parseStatus($itemData['status'] ?? null, $index);
        $position = $this->parsePosition($itemData['position'] ?? null, $index);
        $parent = $this->resolveParent($itemData['parent_unique_name'] ?? null, $uniqueName, $existingItems, $index);

        $draftMenuItem
            ->setLabels($labels)
            ->setUniqueName($uniqueName)
            ->setTargetType($targetType)
            ->setStatus($status)
            ->setPosition($position)
            ->setParent($parent);

        $this->applyTargetConfiguration($draftMenuItem, $itemData, $targetType, $index);
        $draftMenuItem->normalizeTargetConfiguration();

        $violations = $this->validator->validate($draftMenuItem);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $propertyPath = trim((string) $violation->getPropertyPath());
                $messages[] = sprintf(
                    'menu_items[%d]%s: %s: %s',
                    $index,
                    '' !== $uniqueName ? sprintf(' (%s)', $uniqueName) : '',
                    '' !== $propertyPath ? $propertyPath : 'menu_item',
                    $this->normalizeViolationMessage((string) $violation->getMessage())
                );
            }

            throw new TopMenuImportException(implode(' ', $messages));
        }

        $menuItem
            ->setLabels($draftMenuItem->getLabels())
            ->setUniqueName($draftMenuItem->getUniqueName())
            ->setTargetType($draftMenuItem->getTargetType())
            ->setExternalUrl($draftMenuItem->getExternalUrl())
            ->setExternalUrlOpenInNewWindow($draftMenuItem->isExternalUrlOpenInNewWindow())
            ->setArticleCategory($draftMenuItem->getArticleCategory())
            ->setArticle($draftMenuItem->getArticle())
            ->setPosition($draftMenuItem->getPosition())
            ->setStatus($draftMenuItem->getStatus())
            ->setParent($draftMenuItem->getParent());
    }

    /**
     * @param array<string, mixed> $itemData
     */
    private function requireLabels(array $itemData, int $index): array
    {
        $labels = $itemData['labels'] ?? null;
        if (!is_array($labels) || [] === $labels) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].labels is required and must contain translations.', $index));
        }

        $normalizedLabels = [];
        foreach ($labels as $language => $label) {
            if (!is_string($language) || !is_string($label) || '' === trim($language) || '' === trim($label)) {
                throw new TopMenuImportException(sprintf('Field menu_items[%d].labels must contain language => text pairs.', $index));
            }

            $normalizedLabels[strtolower(trim($language))] = trim($label);
        }

        if ([] === $normalizedLabels) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].labels is required and must contain translations.', $index));
        }

        return $normalizedLabels;
    }

    /**
     * @param array<string, TopMenuItem> $existingItems
     */
    private function resolveParent(mixed $value, string $uniqueName, array $existingItems, int $index): ?TopMenuItem
    {
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].parent_unique_name must be a string or null.', $index));
        }

        $parentUniqueName = trim($value);
        if ('' === $parentUniqueName) {
            return null;
        }

        if ($parentUniqueName === $uniqueName) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].parent_unique_name cannot reference the same item.', $index));
        }

        return $existingItems[$parentUniqueName] ?? $this->topMenuItemRepository->findOneByUniqueName($parentUniqueName)
            ?? throw new TopMenuImportException(sprintf(
                'Parent "%s" was not found for menu item "%s".',
                $parentUniqueName,
                $uniqueName
            ));
    }

    /**
     * @param array<string, mixed> $itemData
     */
    private function applyTargetConfiguration(TopMenuItem $menuItem, array $itemData, TopMenuItemTargetType $targetType, int $index): void
    {
        match ($targetType) {
            TopMenuItemTargetType::NONE, TopMenuItemTargetType::BLOG_HOME => null,
            TopMenuItemTargetType::EXTERNAL_URL => $menuItem
                ->setExternalUrl($this->requireString($itemData, 'external_url', $index, true, 'menu_items'))
                ->setExternalUrlOpenInNewWindow($this->parseBoolean($itemData['external_url_open_in_new_window'] ?? false, 'external_url_open_in_new_window', $index)),
            TopMenuItemTargetType::ARTICLE_CATEGORY => $menuItem->setArticleCategory($this->resolveCategory($itemData['category_slug'] ?? null, $index)),
            TopMenuItemTargetType::ARTICLE => $menuItem->setArticle($this->resolveArticle($itemData['article_slug'] ?? null, $index)),
        };
    }

    private function resolveCategory(mixed $value, int $index): \App\Entity\ArticleCategory
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].category_slug is required for target_type=article_category.', $index));
        }

        $category = $this->articleCategoryRepository->findOneBy(['slug' => trim($value)]);
        if (!$category instanceof \App\Entity\ArticleCategory) {
            throw new TopMenuImportException(sprintf('Category with slug "%s" was not found.', trim($value)));
        }

        return $category;
    }

    private function resolveArticle(mixed $value, int $index): \App\Entity\Article
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].article_slug is required for target_type=article.', $index));
        }

        $article = $this->articleRepository->findOneBySlug(trim($value));
        if (!$article instanceof \App\Entity\Article) {
            throw new TopMenuImportException(sprintf('Article with slug "%s" was not found.', trim($value)));
        }

        return $article;
    }

    private function parseTargetType(mixed $value, int $index): TopMenuItemTargetType
    {
        if (!is_string($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].target_type must be a string.', $index));
        }

        $targetType = TopMenuItemTargetType::tryFrom(trim($value));
        if (null === $targetType) {
            throw new TopMenuImportException(sprintf(
                'Field menu_items[%d].target_type has unsupported value "%s".',
                $index,
                $value
            ));
        }

        return $targetType;
    }

    private function parseStatus(mixed $value, int $index): TopMenuItemStatus
    {
        if (!is_string($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].status must be a string.', $index));
        }

        $status = TopMenuItemStatus::tryFrom(trim($value));
        if (null === $status) {
            throw new TopMenuImportException(sprintf(
                'Field menu_items[%d].status has unsupported value "%s".',
                $index,
                $value
            ));
        }

        return $status;
    }

    private function parsePosition(mixed $value, int $index): int
    {
        if (!is_int($value) && !(is_string($value) && preg_match('/^\d+$/', $value))) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].position must be an integer greater than or equal to zero.', $index));
        }

        $position = (int) $value;
        if ($position < 0) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].position must be an integer greater than or equal to zero.', $index));
        }

        return $position;
    }

    private function parseBoolean(mixed $value, string $field, int $index): bool
    {
        if (!is_bool($value)) {
            throw new TopMenuImportException(sprintf('Field menu_items[%d].%s must be true or false.', $index, $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $itemData
     */
    private function requireString(array $itemData, string $field, int $index, bool $trim = false, string $collection = 'menu_items'): string
    {
        $value = $itemData[$field] ?? null;
        if (!is_string($value)) {
            throw new TopMenuImportException(sprintf('Field %s[%d].%s is required and must be a string.', $collection, $index, $field));
        }

        $value = $trim ? trim($value) : $value;
        if ('' === $value) {
            throw new TopMenuImportException(sprintf('Field %s[%d].%s is required.', $collection, $index, $field));
        }

        return $value;
    }

    private function normalizeViolationMessage(string $message): string
    {
        return match ($message) {
            'validation_top_menu_unique_name_required' => 'unique_name is required.',
            'validation_top_menu_unique_name_too_long' => 'unique_name can be at most 255 characters long.',
            'validation_top_menu_external_url_required' => 'URL is required.',
            'validation_top_menu_external_url_too_long' => 'URL can be at most 500 characters long.',
            'validation_top_menu_external_url_invalid' => 'URL is invalid.',
            'validation_top_menu_category_required' => 'article category is required.',
            'validation_top_menu_article_category_required' => 'article category is required.',
            'validation_top_menu_article_required' => 'article is required.',
            'validation_top_menu_position_non_negative' => 'position must be greater than or equal to zero.',
            'validation_top_menu_parent_self' => 'item cannot be its own parent.',
            'validation_top_menu_parent_cycle' => 'cyclic parent-child relation detected.',
            'validation_top_menu_parent_depth' => 'parent must be a top-level menu item.',
            default => $message,
        };
    }
}
