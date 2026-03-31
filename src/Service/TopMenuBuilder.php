<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TopMenuItem;
use App\Enum\TopMenuItemTargetType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TopMenuBuilder
{
    public function __construct(
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    /**
     * @param list<TopMenuItem> $items
     * @return list<array<string, mixed>>
     */
    public function buildActiveTree(array $items): array
    {
        return $this->buildActiveTreeForLanguage($items, $this->userLanguageResolver->getLanguage());
    }

    /**
     * @param list<TopMenuItem> $items
     * @return list<array<string, mixed>>
     */
    public function buildActiveTreeForLanguage(array $items, string $language): array
    {
        $childrenByParentId = [];
        $roots = [];

        foreach ($items as $item) {
            if (!$item->isActive()) {
                continue;
            }

            $parent = $item->getParent();
            if (null === $parent) {
                $roots[] = $item;

                continue;
            }

            if (!$parent->isActive()) {
                continue;
            }

            $childrenByParentId[$parent->getId() ?? 0][] = $item;
        }

        return $this->buildBranch($roots, $childrenByParentId, $language);
    }

    /**
     * @param list<TopMenuItem> $items
     * @param array<int, list<TopMenuItem>> $childrenByParentId
     * @return list<array<string, mixed>>
     */
    private function buildBranch(array $items, array $childrenByParentId, string $language): array
    {
        $branch = [];

        foreach ($items as $item) {
            $resolved = $this->resolveMenuItem($item, $language);
            if (null === $resolved) {
                continue;
            }

            $children = $childrenByParentId[$item->getId() ?? 0] ?? [];
            $resolved['children'] = $this->buildBranch($children, $childrenByParentId, $language);
            $resolved['has_children'] = [] !== $resolved['children'];

            $branch[] = $resolved;
        }

        return $branch;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMenuItem(TopMenuItem $item, string $language): ?array
    {
        $label = $item->getLocalizedLabel($language);
        if ('' === $label) {
            return null;
        }

        $labelPl = $item->getLabel('pl', 'en') ?? $label;
        $labelEn = $item->getLabel('en', 'pl') ?? $label;
        $targetType = $item->getTargetType();

        $url = match ($targetType) {
            TopMenuItemTargetType::NONE => null,
            TopMenuItemTargetType::EXTERNAL_URL => $item->getExternalUrl(),
            TopMenuItemTargetType::BLOG_HOME => $this->urlGenerator->generate('blog_index'),
            TopMenuItemTargetType::ARTICLE_CATEGORY => $this->resolveCategoryUrl($item),
            TopMenuItemTargetType::ARTICLE => $this->resolveArticleUrl($item),
        };

        if (TopMenuItemTargetType::NONE !== $targetType && (null === $url || '' === $url)) {
            return null;
        }

        return [
            'id' => $item->getId(),
            'label' => $label,
            'label_pl' => $labelPl,
            'label_en' => $labelEn,
            'url' => $url,
            'external' => TopMenuItemTargetType::EXTERNAL_URL === $targetType,
            'open_in_new_window' => TopMenuItemTargetType::EXTERNAL_URL === $targetType && $item->isExternalUrlOpenInNewWindow(),
        ];
    }

    private function resolveCategoryUrl(TopMenuItem $item): ?string
    {
        $category = $item->getArticleCategory();
        if (null === $category || !$category->isActive()) {
            return null;
        }

        return $this->urlGenerator->generate('blog_category', [
            'slug' => $category->getSlug(),
        ]);
    }

    private function resolveArticleUrl(TopMenuItem $item): ?string
    {
        $article = $item->getArticle();
        if (null === $article || !$article->isPublished()) {
            return null;
        }

        return $this->urlGenerator->generate('blog_show', [
            'slug' => $article->getSlug(),
        ]);
    }
}
