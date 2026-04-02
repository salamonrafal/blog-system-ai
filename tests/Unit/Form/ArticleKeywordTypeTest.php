<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\ArticleKeyword;
use App\Enum\ArticleKeywordLanguage;
use App\Form\ArticleKeywordType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArticleKeywordTypeTest extends TestCase
{
    public function testConfigureOptionsSetsKeywordDataClass(): void
    {
        $resolver = new OptionsResolver();

        (new ArticleKeywordType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(ArticleKeyword::class, $options['data_class']);
    }

    public function testBuildFormRegistersExpectedFieldsAndOptions(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $form = $factory->create(ArticleKeywordType::class, new ArticleKeyword());

        $this->assertInstanceOf(EnumType::class, $form->get('language')->getConfig()->getType()->getInnerType());
        $this->assertSame(ArticleKeywordLanguage::class, $form->get('language')->getConfig()->getOption('class'));
        $this->assertInstanceOf(TextType::class, $form->get('color')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('color')->getConfig()->getOption('required'));
        $this->assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        $this->assertSame('', $form->get('name')->getConfig()->getOption('empty_data'));
        $this->assertSame(255, $form->get('name')->getConfig()->getOption('attr')['maxlength']);
        $this->assertInstanceOf(EnumType::class, $form->get('status')->getConfig()->getType()->getInnerType());
    }
}
