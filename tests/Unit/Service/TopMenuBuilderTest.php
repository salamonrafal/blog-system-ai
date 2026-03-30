<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Enum\TopMenuItemTargetType;
use App\Service\ArticleSlugger;
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

        $slugger = $this->createMock(ArticleSlugger::class);
        $slugger->method('slugify')->with('PHP')->willReturn('php');

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

        $category = (new ArticleCategory())->setName('PHP')->setTitle('en', 'PHP');
        $article = (new Article())->setTitle('Article')->setSlug('article');
        $parent = (new TopMenuItem())->setLabel('en', 'Blog')->setTargetType(TopMenuItemTargetType::BLOG_HOME);
        $this->setEntityId($parent, 1);
        $child = (new TopMenuItem())->setLabel('en', 'PHP')->setTargetType(TopMenuItemTargetType::ARTICLE_CATEGORY)->setArticleCategory($category)->setParent($parent);
        $articleItem = (new TopMenuItem())->setLabel('en', 'Article')->setTargetType(TopMenuItemTargetType::ARTICLE)->setArticle($article);

        $builder = new TopMenuBuilder($languageResolver, $slugger, $urlGenerator);
        $tree = $builder->buildActiveTree([$parent, $child, $articleItem]);

        $this->assertCount(2, $tree);
        $this->assertSame('Blog', $tree[0]['label']);
        $this->assertSame('/', $tree[0]['url']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('/category/php', $tree[0]['children'][0]['url']);
        $this->assertSame('/articles/article', $tree[1]['url']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setValue($entity, $id);
    }
}
