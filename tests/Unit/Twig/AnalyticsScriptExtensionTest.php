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
            ->method('findEnabledForScopes')
            ->with(
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
            ->method('findEnabledForScopes');

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
            ->method('findEnabledForScopes')
            ->with(
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

    public function testCachesEnabledScriptsForCurrentRequestAndSplitsByPlacement(): void
    {
        $headScript = (new AnalyticsScript())
            ->setPageName('head_script')
            ->setName('Head script')
            ->setPlacement(AnalyticsScriptPlacement::HEAD)
            ->setScript('<script></script>');
        $bodyScript = (new AnalyticsScript())
            ->setPageName('body_script')
            ->setName('Body script')
            ->setPlacement(AnalyticsScriptPlacement::BODY_END)
            ->setScript('<script></script>');

        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $repository
            ->expects($this->once())
            ->method('findEnabledForScopes')
            ->with([AnalyticsScriptScope::ALL_PUBLIC, AnalyticsScriptScope::ARTICLE])
            ->willReturn([$headScript, $bodyScript]);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'blog_show');
        $requestStack->push($request);

        $extension = $this->createExtension($repository, $requestStack);

        $this->assertSame([$headScript], $extension->getAnalyticsScripts('head'));
        $this->assertSame([$bodyScript], $extension->getAnalyticsScripts('body_end'));
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

    public function testUsesControllerProvidedAnalyticsPageContextBeforeRepositoryLookup(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'blog_show');
        $request->attributes->set(AnalyticsScriptExtension::REQUEST_ATTRIBUTE_PAGE_TYPE, 'article');
        $request->attributes->set(AnalyticsScriptExtension::REQUEST_ATTRIBUTE_PAGE_NAME_BASE, 'Loaded Article Title');
        $requestStack->push($request);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->never())
            ->method('findOneBySlug');

        $extension = $this->createExtension(
            $repository,
            $requestStack,
            articleRepository: $articleRepository,
            languageResolver: new UserLanguageResolver($requestStack),
        );
        $script = (new AnalyticsScript())->setScript('VAR_PAGE_NAME|VAR_PAGE_TYPE');

        $this->assertSame(
            '"article_page_loaded_article_title"|"article"',
            $extension->renderAnalyticsScriptSnippet($script),
        );
    }

    public function testReplacesOnlyStandaloneAnalyticsVariableTokens(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set('_route', 'blog_show');
        $request->attributes->set(AnalyticsScriptExtension::REQUEST_ATTRIBUTE_PAGE_TYPE, 'article');
        $request->attributes->set(AnalyticsScriptExtension::REQUEST_ATTRIBUTE_PAGE_NAME_BASE, 'Loaded Article Title');
        $requestStack->push($request);

        $extension = $this->createExtension(
            $repository,
            $requestStack,
            languageResolver: new UserLanguageResolver($requestStack),
        );
        $script = (new AnalyticsScript())->setScript(<<<'HTML'
<script>
window.VAR_PAGE_NAME_MAP = "VAR_PAGE_NAME";
const page = VAR_PAGE_NAME;
const logged = VAR_IS_LOGGED_USER;
// VAR_PAGE_TYPE
/* VAR_USER_LANGUAGE */
</script>
HTML);

        $this->assertSame(<<<'HTML'
<script>
window.VAR_PAGE_NAME_MAP = "VAR_PAGE_NAME";
const page = "article_page_loaded_article_title";
const logged = false;
// VAR_PAGE_TYPE
/* VAR_USER_LANGUAGE */
</script>
HTML, $extension->renderAnalyticsScriptSnippet($script));
    }

    public function testResolvesAnalyticsVariablesPerCurrentRequest(): void
    {
        $repository = $this->createMock(AnalyticsScriptRepository::class);
        $requestStack = new RequestStack();

        $firstRequest = new Request(cookies: ['user_language' => 'pl']);
        $firstRequest->attributes->set('_route', 'blog_show');
        $firstRequest->attributes->set('slug', 'pierwszy-artykul');
        $requestStack->push($firstRequest);

        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository
            ->expects($this->exactly(2))
            ->method('findOneBySlug')
            ->willReturnCallback(static fn (string $slug): ?Article => match ($slug) {
                'pierwszy-artykul' => (new Article())->setTitle('Pierwszy artykuł'),
                'drugi-artykul' => (new Article())->setTitle('Second article'),
                default => null,
            });

        $extension = $this->createExtension(
            $repository,
            $requestStack,
            articleRepository: $articleRepository,
            languageResolver: new UserLanguageResolver($requestStack),
        );
        $script = (new AnalyticsScript())->setScript('VAR_PAGE_NAME|VAR_USER_LANGUAGE');

        $this->assertSame(
            '"article_page_pierwszy_artykul"|"pl"',
            $extension->renderAnalyticsScriptSnippet($script),
        );

        $secondRequest = new Request(cookies: ['user_language' => 'en']);
        $secondRequest->attributes->set('_route', 'blog_show');
        $secondRequest->attributes->set('slug', 'drugi-artykul');
        $requestStack->push($secondRequest);

        $this->assertSame(
            '"article_page_second_article"|"en"',
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
