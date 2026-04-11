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
use Symfony\Component\Validator\Validation;

final class TopMenuItemTest extends TestCase
{
    public function testMenuItemExposesAssignedValues(): void
    {
        $category = (new ArticleCategory())->setName('PHP')->setTitle('pl', 'PHP');
        $article = (new Article())->setTitle('Hello world')->setSlug('hello-world')->setLanguage(ArticleLanguage::EN);
        $parent = (new TopMenuItem())->setLabel('pl', 'Blog');

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Kontakt', 'en' => 'Contact'])
            ->setUniqueName('kontakt')
            ->setTargetType(TopMenuItemTargetType::EXTERNAL_URL)
            ->setExternalUrl('https://example.com/contact')
            ->setExternalUrlOpenInNewWindow(true)
            ->setArticleCategory($category)
            ->setArticle($article)
            ->setParent($parent)
            ->setPosition(7)
            ->setStatus(TopMenuItemStatus::INACTIVE);

        $this->assertSame('Kontakt', $menuItem->getLabel('pl'));
        $this->assertSame('kontakt', $menuItem->getUniqueName());
        $this->assertSame('Contact', $menuItem->getLocalizedLabel('en'));
        $this->assertSame(TopMenuItemTargetType::EXTERNAL_URL, $menuItem->getTargetType());
        $this->assertSame('https://example.com/contact', $menuItem->getExternalUrl());
        $this->assertTrue($menuItem->isExternalUrlOpenInNewWindow());
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
            ->setExternalUrlOpenInNewWindow(true)
            ->setPosition(-5);

        $this->assertSame(['en' => 'Home', 'pl' => 'Start'], $menuItem->getLabels());
        $this->assertSame('https://example.com', $menuItem->getExternalUrl());
        $this->assertTrue($menuItem->isExternalUrlOpenInNewWindow());
        $this->assertSame(-5, $menuItem->getPosition());
    }

    public function testExternalUrlValidationIsAppliedOnlyForExternalLinks(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $blogHomeItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setExternalUrl('not-a-valid-url');

        $externalItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Kontakt', 'en' => 'Contact'])
            ->setUniqueName('kontakt')
            ->setTargetType(TopMenuItemTargetType::EXTERNAL_URL)
            ->setExternalUrl('not-a-valid-url');

        $this->assertSame(0, $validator->validate($blogHomeItem)->count());

        $violations = $validator->validate($externalItem);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('validation_top_menu_external_url_invalid', $violations[0]->getMessage());
    }

    public function testNoneTargetDoesNotRequireRedirectTarget(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Usługi', 'en' => 'Services'])
            ->setUniqueName('uslugi')
            ->setTargetType(TopMenuItemTargetType::NONE);

        $this->assertSame(0, $validator->validate($menuItem)->count());
    }

    public function testNegativePositionTriggersValidationError(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setPosition(-1);

        $violations = $validator->validate($menuItem);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('validation_top_menu_position_non_negative', $violations[0]->getMessage());
    }

    public function testSelectingNestedParentTriggersValidationError(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $topLevelParent = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME);
        $nestedParent = (new TopMenuItem())
            ->setLabels(['pl' => 'PHP', 'en' => 'PHP'])
            ->setUniqueName('php')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setParent($topLevelParent);
        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Symfony', 'en' => 'Symfony'])
            ->setUniqueName('symfony')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setParent($nestedParent);

        $violations = $validator->validate($menuItem);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('validation_top_menu_parent_depth', $violations[0]->getMessage());
    }

    public function testSelfParentTriggersOnlySelfValidationError(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME);
        $menuItem->setParent($menuItem);

        $violations = $validator->validate($menuItem);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertSame(['validation_top_menu_parent_self'], $messages);
    }

    public function testCycleParentTriggersOnlyCycleValidationError(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $grandparent = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME);
        $parent = (new TopMenuItem())
            ->setLabels(['pl' => 'PHP', 'en' => 'PHP'])
            ->setUniqueName('php')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setParent($grandparent);
        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Symfony', 'en' => 'Symfony'])
            ->setUniqueName('symfony')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setParent($parent);

        $grandparent->setParent($menuItem);

        $violations = $validator->validate($menuItem);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertSame(['validation_top_menu_parent_cycle'], $messages);
    }

    public function testNormalizeTargetConfigurationClearsFieldsNotMatchingCurrentTargetType(): void
    {
        $category = (new ArticleCategory())->setName('PHP')->setSlug('php');
        $article = (new Article())->setTitle('Hello world')->setSlug('hello-world')->setLanguage(ArticleLanguage::EN);

        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Blog', 'en' => 'Blog'])
            ->setUniqueName('blog')
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME)
            ->setExternalUrl('https://example.com')
            ->setExternalUrlOpenInNewWindow(true)
            ->setArticleCategory($category)
            ->setArticle($article);

        $menuItem->normalizeTargetConfiguration();

        $this->assertNull($menuItem->getExternalUrl());
        $this->assertFalse($menuItem->isExternalUrlOpenInNewWindow());
        $this->assertNull($menuItem->getArticleCategory());
        $this->assertNull($menuItem->getArticle());
    }

    public function testLifecycleCallbacksRejectPersistingMenuItemWithoutUniqueName(): void
    {
        $menuItem = (new TopMenuItem())
            ->setLabels(['pl' => 'Kontakt', 'en' => 'Contact'])
            ->setTargetType(TopMenuItemTargetType::BLOG_HOME);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Top menu item uniqueName cannot be empty when persisting.');

        $menuItem->onPrePersist();
    }
}
