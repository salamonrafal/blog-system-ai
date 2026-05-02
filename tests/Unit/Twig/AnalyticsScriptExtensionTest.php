<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Entity\AnalyticsScript;
use App\Entity\Article;
use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use App\Repository\AnalyticsScriptRepository;
use App\Repository\ArticleCategoryRepository;
use App\Repository\ArticleKeywordRepository;
use App\Repository\ArticleRepository;
use App\Service\ArticleSlugger;
use App\Service\UserLanguageResolver;
use App\Twig\AnalyticsScriptExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

final class AnalyticsScriptExtensionTest extends TestCase
{
    public function testReturnsEnabledScriptsForPublicRouteScopeAndPlacement(): void
    {
        $expectedScript = (new AnalyticsScript())
            ->setPageName('ga_all')
            ->setName('GA')
            ->setScript('<script></script>');

        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findEnabledForPlacementAndScopes')
            ->with(
                AnalyticsScriptPlacement::HEAD,
                [AnalyticsScriptScope::ALL_PUBLIC, AnalyticsScriptScope::ARTICLE],
            )
            ->willReturn([$expectedScript]);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'blog_show');
        $requestStack->push($request);

        $extension = $this->createExtension($repository, $requestStack);

        $this->assertSame([$expectedScript], $extension->getAnalyticsScripts('head'));
    }

    public function testSkipsAdminRoutes(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->never())
            ->method('findEnabledForPlacementAndScopes');

        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'admin_dashboard');
        $requestStack->push($request);

        $extension = $this->createExtension($repository, $requestStack);

        $this->assertSame([], $extension->getAnalyticsScripts('head'));
    }

    public function testUnknownPublicRoutesStillReceiveAllPublicScripts(): void
    {
        $expectedScript = (new AnalyticsScript())
            ->setPageName('ga_all')
            ->setName('GA')
            ->setScript('<script></script>');

        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findEnabledForPlacementAndScopes')
            ->with(
                AnalyticsScriptPlacement::HEAD,
                [AnalyticsScriptScope::ALL_PUBLIC],
            )
            ->willReturn([$expectedScript]);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'app_login');
        $requestStack->push($request);

        $extension = $this->createExtension($repository, $requestStack);

        $this->assertSame([$expectedScript], $extension->getAnalyticsScripts('head'));
    }

    public function testReplacesAnalyticsVariablesWithCurrentPageContext(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'blog_show');
        $request->attributes->set('slug', 'pierwszy-artykul');
        $requestStack->push($request);

        $article = (new Article())->setTitle('Tytuł strony żółć');
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->with('pierwszy-artykul')
            ->willReturn($article);

        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver
            ->expects($this->once())
            ->method('getLanguage')
            ->willReturn('pl');

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(new class implements UserInterface {
                public function getRoles(): array
                {
                    return ['ROLE_ADMIN'];
                }

                public function eraseCredentials(): void
                {
                }

                public function getUserIdentifier(): string
                {
                    return 'admin@example.com';
                }
            });

        $extension = $this->createExtension(
            $repository,
            $requestStack,
            articleRepository: $articleRepository,
            languageResolver: $languageResolver,
            security: $security,
        );
        $script = (new AnalyticsScript())->setScript(
            '<script>window.analytics = {page: VAR_PAGE_NAME, type: VAR_PAGE_TYPE, logged: VAR_IS_LOGGED_USER, lang: VAR_USER_LANGUAGE};</script>',
        );

        $this->assertSame(
            '<script>window.analytics = {page: "article_page_tytul_strony_zolc", type: "article", logged: true, lang: "pl"};</script>',
            $extension->renderAnalyticsScriptSnippet($script),
        );
    }

    private function createExtension(
        AnalyticsScriptRepository $repository,
        RequestStack $requestStack,
        ?ArticleRepository $articleRepository = null,
        ?ArticleCategoryRepository $articleCategoryRepository = null,
        ?ArticleKeywordRepository $articleKeywordRepository = null,
        ?UserLanguageResolver $languageResolver = null,
        ?Security $security = null,
    ): AnalyticsScriptExtension {
        if (null === $languageResolver) {
            $languageResolver = $this->createMock(UserLanguageResolver::class);
            $languageResolver
                ->method('getLanguage')
                ->willReturn('en');
        }

        if (null === $security) {
            $security = $this->createMock(Security::class);
            $security
                ->method('getUser')
                ->willReturn(null);
        }

        return new AnalyticsScriptExtension(
            $repository,
            $requestStack,
            $articleRepository ?? $this->createMock(ArticleRepository::class),
            $articleCategoryRepository ?? $this->createMock(ArticleCategoryRepository::class),
            $articleKeywordRepository ?? $this->createMock(ArticleKeywordRepository::class),
            new ArticleSlugger(),
            $languageResolver,
            $security,
        );
    }
}
