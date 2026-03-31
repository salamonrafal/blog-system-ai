<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Enum\ArticleStatus;
use App\Enum\TopMenuItemTargetType;
use App\Service\TopMenuBuilder;
use App\Service\UserLanguageResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TopMenuBuilderTest extends TestCase
{
    public function testBuildActiveTreeResolvesUrlsAndChildren(): void
    {
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver->method('getLanguage')->willReturn('en');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                return match ($route) {
                    'blog_index' => '/',
                    'blog_category' => '/category/'.$parameters['slug'],
                    'blog_show' => '/articles/'.$parameters['slug'],
                    default => '',
                };
            });

        $category = (new ArticleCategory())->setName('PHP')->setSlug('php')->setTitle('en', 'PHP');
        $article = (new Article())
            ->setTitle('Article')
            ->setSlug('article')
            ->setStatus(ArticleStatus::PUBLISHED);
        $parent = (new TopMenuItem())->setLabel('en', 'Blog')->setTargetType(TopMenuItemTargetType::BLOG_HOME);
        $this->setEntityId($parent, 1);
        $child = (new TopMenuItem())->setLabel('en', 'PHP')->setTargetType(TopMenuItemTargetType::ARTICLE_CATEGORY)->setArticleCategory($category)->setParent($parent);
        $articleItem = (new TopMenuItem())->setLabel('en', 'Article')->setTargetType(TopMenuItemTargetType::ARTICLE)->setArticle($article);
        $externalItem = (new TopMenuItem())->setLabel('en', 'Docs')->setTargetType(TopMenuItemTargetType::EXTERNAL_URL)->setExternalUrl('https://example.com/docs')->setExternalUrlOpenInNewWindow(true);

        $builder = new TopMenuBuilder($languageResolver, $urlGenerator);
        $tree = $builder->buildActiveTree([$parent, $child, $articleItem, $externalItem]);

        $this->assertCount(3, $tree);
        $this->assertSame('Blog', $tree[0]['label']);
        $this->assertSame('Blog', $tree[0]['label_pl']);
        $this->assertSame('Blog', $tree[0]['label_en']);
        $this->assertSame('/', $tree[0]['url']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('/category/php', $tree[0]['children'][0]['url']);
        $this->assertSame('/articles/article', $tree[1]['url']);
        $this->assertSame('https://example.com/docs', $tree[2]['url']);
        $this->assertTrue($tree[2]['external']);
        $this->assertTrue($tree[2]['open_in_new_window']);
    }

    public function testBuildActiveTreeSkipsChildrenOfInactiveParent(): void
    {
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver->method('getLanguage')->willReturn('en');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                return match ($route) {
                    'blog_index' => '/',
                    'blog_category' => '/category/'.$parameters['slug'],
                    default => '',
                };
            });

        $category = (new ArticleCategory())->setName('PHP')->setSlug('php')->setTitle('en', 'PHP');
        $inactiveParent = (new TopMenuItem())
            ->setLabel('en', 'Hidden')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setStatus(\App\Enum\TopMenuItemStatus::INACTIVE);
        $this->setEntityId($inactiveParent, 10);

        $child = (new TopMenuItem())
            ->setLabel('en', 'PHP')
            ->setTargetType(TopMenuItemTargetType::ARTICLE_CATEGORY)
            ->setArticleCategory($category)
            ->setParent($inactiveParent);

        $builder = new TopMenuBuilder($languageResolver, $urlGenerator);
        $tree = $builder->buildActiveTree([$inactiveParent, $child]);

        $this->assertSame([], $tree);
    }

    public function testBuildActiveTreeSkipsUnpublishedArticleLinks(): void
    {
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver->method('getLanguage')->willReturn('en');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $article = (new Article())
            ->setTitle('Draft article')
            ->setSlug('draft-article')
            ->setStatus(ArticleStatus::DRAFT);

        $articleItem = (new TopMenuItem())
            ->setLabel('en', 'Draft article')
            ->setTargetType(TopMenuItemTargetType::ARTICLE)
            ->setArticle($article);

        $builder = new TopMenuBuilder($languageResolver, $urlGenerator);
        $tree = $builder->buildActiveTree([$articleItem]);

        $this->assertSame([], $tree);
    }

    public function testBuildActiveTreeKeepsParentWithoutRedirectTarget(): void
    {
        $languageResolver = $this->createMock(UserLanguageResolver::class);
        $languageResolver->method('getLanguage')->willReturn('en');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                return match ($route) {
                    'blog_category' => '/category/'.$parameters['slug'],
                    default => '',
                };
            });

        $category = (new ArticleCategory())->setName('PHP')->setSlug('php')->setTitle('en', 'PHP');
        $parent = (new TopMenuItem())
            ->setLabel('en', 'Services')
            ->setTargetType(TopMenuItemTargetType::NONE);
        $this->setEntityId($parent, 100);

        $child = (new TopMenuItem())
            ->setLabel('en', 'PHP')
            ->setTargetType(TopMenuItemTargetType::ARTICLE_CATEGORY)
            ->setArticleCategory($category)
            ->setParent($parent);

        $builder = new TopMenuBuilder($languageResolver, $urlGenerator);
        $tree = $builder->buildActiveTree([$parent, $child]);

        $this->assertCount(1, $tree);
        $this->assertSame('Services', $tree[0]['label']);
        $this->assertNull($tree[0]['url']);
        $this->assertFalse($tree[0]['external']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('/category/php', $tree[0]['children'][0]['url']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}
