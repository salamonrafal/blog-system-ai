<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\ArticleKeyword;
use App\Enum\ArticleKeywordLanguage;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use App\Form\ArticleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArticleTypeTest extends TestCase
{
    public function testConfigureOptionsSetsArticleDataClass(): void
    {
        $resolver = new OptionsResolver();

        (new ArticleType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(Article::class, $options['data_class']);
        $this->assertSame([], $options['categories']);
    }

    public function testBuildFormRegistersExpectedFieldsAndImportantOptions(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $categories = [
            (new ArticleCategory())->setName('PHP'),
            (new ArticleCategory())->setName('AI'),
        ];
        $keywords = [
            (new ArticleKeyword())->setName('php')->setLanguage(ArticleKeywordLanguage::ALL),
            (new ArticleKeyword())->setName('ai')->setLanguage(ArticleKeywordLanguage::EN),
        ];
        $form = $factory->create(ArticleType::class, new Article(), [
            'categories' => $categories,
            'keywords' => $keywords,
        ]);

        $this->assertInstanceOf(TextType::class, $form->get('title')->getConfig()->getType()->getInnerType());
        $this->assertSame(255, $form->get('title')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(EnumType::class, $form->get('language')->getConfig()->getType()->getInnerType());
        $this->assertSame(ArticleLanguage::class, $form->get('language')->getConfig()->getOption('class'));

        $this->assertInstanceOf(TextareaType::class, $form->get('excerpt')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('excerpt')->getConfig()->getOption('required'));

        $this->assertInstanceOf(CheckboxType::class, $form->get('headlineImageEnabled')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('headlineImageEnabled')->getConfig()->getOption('required'));

        $this->assertInstanceOf(TextType::class, $form->get('headlineImage')->getConfig()->getType()->getInnerType());
        $this->assertSame(500, $form->get('headlineImage')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('content')->getConfig()->getType()->getInnerType());
        $this->assertSame(16, $form->get('content')->getConfig()->getOption('attr')['rows']);

        $this->assertInstanceOf(EnumType::class, $form->get('status')->getConfig()->getType()->getInnerType());
        $this->assertSame(ArticleStatus::class, $form->get('status')->getConfig()->getOption('class'));

        $this->assertInstanceOf(ChoiceType::class, $form->get('category')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('category')->getConfig()->getOption('required'));
        $this->assertSame('---', $form->get('category')->getConfig()->getOption('placeholder'));
        $this->assertSame($categories, $form->get('category')->getConfig()->getOption('choices'));

        $this->assertInstanceOf(ChoiceType::class, $form->get('keywords')->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->get('keywords')->getConfig()->getOption('multiple'));
        $this->assertFalse($form->get('keywords')->getConfig()->getOption('required'));
        $this->assertSame($keywords, $form->get('keywords')->getConfig()->getOption('choices'));
        $keywordChoiceAttr = $form->get('keywords')->getConfig()->getOption('choice_attr');
        $allKeywordAttrs = $keywordChoiceAttr($keywords[0]);
        $enKeywordAttrs = $keywordChoiceAttr($keywords[1]);
        $this->assertSame('article_keyword_language_all', $allKeywordAttrs['data-keyword-scope-key']);
        $this->assertSame('article_language_en', $enKeywordAttrs['data-keyword-scope-key']);

        $this->assertInstanceOf(DateTimeType::class, $form->get('publishedAt')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('publishedAt')->getConfig()->getOption('required'));
        $this->assertSame('single_text', $form->get('publishedAt')->getConfig()->getOption('widget'));
        $this->assertSame('datetime_immutable', $form->get('publishedAt')->getConfig()->getOption('input'));
        $this->assertSame('UTC', $form->get('publishedAt')->getConfig()->getOption('model_timezone'));
        $this->assertSame('UTC', $form->get('publishedAt')->getConfig()->getOption('view_timezone'));
    }
}
