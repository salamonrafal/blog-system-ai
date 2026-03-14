<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ArticleSlugger;
use App\Tests\TestCase;

final class ArticleSluggerTest extends TestCase
{
    public function testSlugifyNormalizesPolishCharactersAndSpacing(): void
    {
        $slugger = new ArticleSlugger();

        $slug = $slugger->slugify('Zażółć gęślą jaźń w Symfony 7');

        $this->assertSame('zazolc-gesla-jazn-w-symfony-7', $slug);
    }

    public function testSlugifyReturnsLowercaseSlug(): void
    {
        $slugger = new ArticleSlugger();

        $slug = $slugger->slugify('Hello WORLD');

        $this->assertSame('hello-world', $slug);
    }
}
