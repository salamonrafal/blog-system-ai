<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Enum\ArticleLanguage;
use App\Enum\TopMenuItemStatus;
use App\Enum\TopMenuItemTargetType;
use PHPUnit\Framework\TestCase;

final class TopMenuItemTest extends TestCase
{
    public function testMenuItemExposesAssignedValues(): void
    {
        $category = (new ArticleCategory())->setName('PHP')->setTitle('pl', 'PHP');
        $article = (new Article())->setTitle('Hello world')->setSlug('hello-world')->setLanguage(ArticleLanguage::EN);
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Kontakt', 'en' => 'Contact'])
            ->setTargetType(TopMenuItemTargetType::EXTERNAL_URL)
            ->setExternalUrl('https://example.com/contact')
            ->setArticleCategory($category)
            ->setArticle($article)
            ->setParent($parent)
            ->setPosition(7)
            ->setStatus(TopMenuItemStatus::INACTIVE);

        $this->assertSame('Kontakt', $menuItem->getLabel('pl'));
        $this->assertSame('Contact', $menuItem->getLocalizedLabel('en'));
        $this->assertSame(TopMenuItemTargetType::EXTERNAL_URL, $menuItem->getTargetType());
        $this->assertSame('https://example.com/contact', $menuItem->getExternalUrl());
        $this->assertSame($category, $menuItem->getArticleCategory());
        $this->assertSame($article, $menuItem->getArticle());
        $this->assertSame($parent, $menuItem->getParent());
        $this->assertSame(7, $menuItem->getPosition());
        $this->assertSame(TopMenuItemStatus::INACTIVE, $menuItem->getStatus());
        $this->assertFalse($menuItem->isActive());
    }

    public function testMenuItemNormalizesTranslationsAndOptionalFields(): void
    {
        $menuItem = (new TopMenuItem())
            ->setLabels([' PL ' => '  Start  ', 'en' => '  Home  ', 'de' => '   '])
            ->setExternalUrl('  https://example.com  ')
            ->setPosition(-5);

        $this->assertSame(['en' => 'Home', 'pl' => 'Start'], $menuItem->getLabels());
        $this->assertSame('https://example.com', $menuItem->getExternalUrl());
        $this->assertSame(0, $menuItem->getPosition());
    }
}
