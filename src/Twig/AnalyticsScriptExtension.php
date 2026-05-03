<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\AnalyticsScript;
use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use App\Enum\ArticleKeywordLanguage;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleKeywordRepository;
use App\Repository\ArticleRepository;
use App\Repository\AnalyticsScriptRepository;
use App\Service\ArticleSlugger;
use App\Service\UserLanguageResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AnalyticsScriptExtension extends AbstractExtension
{
    private ?int $resolvedVariablesRequestId = null;

    private ?array $resolvedVariables = null;

    public function __construct(
        private readonly AnalyticsScriptRepository $analyticsScriptRepository,
        private readonly RequestStack $requestStack,
        private readonly ArticleRepository $articleRepository,
        private readonly ArticleCategoryRepository $articleCategoryRepository,
        private readonly ArticleKeywordRepository $articleKeywordRepository,
        private readonly ArticleSlugger $articleSlugger,
        private readonly UserLanguageResolver $userLanguageResolver,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('analytics_scripts', $this->getAnalyticsScripts(...)),
            new TwigFunction('analytics_script_snippet', $this->renderAnalyticsScriptSnippet(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return list<AnalyticsScript>
     */
    public function getAnalyticsScripts(string $placement): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $routeName = (string) $request->attributes->get('_route', '');
        if ('' === $routeName || str_starts_with($routeName, 'admin_')) {
            return [];
        }

        $resolvedPlacement = AnalyticsScriptPlacement::tryFrom($placement);
        if (null === $resolvedPlacement) {
            return [];
        }

        $scopes = [AnalyticsScriptScope::ALL_PUBLIC];
        $routeScope = AnalyticsScriptScope::fromRouteName($routeName);
        if (null !== $routeScope) {
            $scopes[] = $routeScope;
        }

        return $this->analyticsScriptRepository->findEnabledForPlacementAndScopes(
            $resolvedPlacement,
            $scopes,
        );
    }

    public function renderAnalyticsScriptSnippet(AnalyticsScript $script): string
    {
        return strtr($script->getScript(), $this->getVariableReplacements());
    }

    /**
     * @return array<string, string>
     */
    private function getVariableReplacements(): array
    {
        $variables = $this->resolveVariables();

        return [
            'VAR_PAGE_NAME' => $this->encodeScriptValue($variables['page_name']),
            'VAR_PAGE_TYPE' => $this->encodeScriptValue($variables['page_type']),
            'VAR_IS_LOGGED_USER' => $variables['is_logged_user'] ? 'true' : 'false',
            'VAR_USER_LANGUAGE' => $this->encodeScriptValue($variables['user_language']),
        ];
    }

    /**
     * @return array{page_name: string, page_type: string, is_logged_user: bool, user_language: string}
     */
    private function resolveVariables(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $requestId = null !== $request ? spl_object_id($request) : null;

        if (null !== $requestId && $requestId === $this->resolvedVariablesRequestId && null !== $this->resolvedVariables) {
            return $this->resolvedVariables;
        }

        $routeName = null !== $request ? (string) $request->attributes->get('_route', '') : '';
        $userLanguage = $this->userLanguageResolver->getLanguage();
        $pageType = $this->resolvePageType($routeName);
        $pageName = $this->resolvePageName($routeName, $pageType, $userLanguage);

        $variables = [
            'page_name' => $pageName,
            'page_type' => $pageType,
            'is_logged_user' => null !== $this->security->getUser(),
            'user_language' => $userLanguage,
        ];

        if (null !== $requestId) {
            $this->resolvedVariablesRequestId = $requestId;
            $this->resolvedVariables = $variables;
        }

        return $variables;
    }

    private function resolvePageType(string $routeName): string
    {
        return match ($routeName) {
            'blog_index' => 'blog_index',
            'blog_show' => 'article',
            'blog_category' => 'category',
            'blog_keyword' => 'keyword',
            default => '' !== $routeName ? $routeName : 'unknown',
        };
    }

    private function resolvePageName(string $routeName, string $pageType, string $userLanguage): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return 'unknown';
        }

        if ('blog_index' === $routeName) {
            return 'blog_index';
        }

        if ('blog_show' === $routeName) {
            $slug = (string) $request->attributes->get('slug', '');
            $article = '' !== $slug ? $this->articleRepository->findOneBySlug($slug) : null;
            $baseName = null !== $article ? $article->getTitle() : $slug;

            return 'article_page_'.$this->slugifyPageName($baseName, 'article');
        }

        if ('blog_category' === $routeName) {
            $slug = (string) $request->attributes->get('slug', '');
            $category = '' !== $slug ? $this->articleCategoryRepository->findOneBy(['slug' => $slug]) : null;
            $baseName = null !== $category ? $category->getTitle($userLanguage) ?? $category->getName() : $slug;

            return 'category_page_'.$this->slugifyPageName($baseName, 'category');
        }

        if ('blog_keyword' === $routeName) {
            $language = ArticleKeywordLanguage::tryFrom(strtolower((string) $request->attributes->get('language', '')));
            $name = (string) $request->attributes->get('name', '');
            $keyword = null !== $language && '' !== $name ? $this->articleKeywordRepository->findOneByLanguageAndName($language, $name) : null;
            $baseName = null !== $keyword ? $keyword->getName() : $name;

            return 'keyword_page_'.$this->slugifyPageName($baseName, 'keyword');
        }

        return $this->slugifyPageName($pageType, 'page');
    }

    private function slugifyPageName(string $value, string $fallback): string
    {
        $slug = $this->articleSlugger->slugify($value);

        return '' !== $slug ? str_replace('-', '_', $slug) : $fallback;
    }

    private function encodeScriptValue(string $value): string
    {
        return json_encode(
            $value,
            \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES,
        ) ?: '""';
    }
}
