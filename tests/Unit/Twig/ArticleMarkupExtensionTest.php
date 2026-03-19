<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Service\ArticleMarkupRenderer;
use App\Twig\ArticleMarkupExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

final class ArticleMarkupExtensionTest extends TestCase
{
    public function testGetFiltersRegistersHtmlSafeArticleMarkupFilter(): void
    {
        $extension = new ArticleMarkupExtension(new ArticleMarkupRenderer());

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('article_markup', $filters[0]->getName());
        $this->assertContains('html', $filters[0]->getSafe(new \Twig\Node\Node()));
    }

    public function testRenderDelegatesToRenderer(): void
    {
        $renderer = new ArticleMarkupRenderer();
        $extension = new ArticleMarkupExtension($renderer);

        $this->assertSame($renderer->render('**tekst**'), $extension->render('**tekst**'));
    }
}
