<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\ArticleCategory;
use App\Form\ArticleCategoryType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArticleCategoryTypeTest extends TestCase
{
    public function testConfigureOptionsSetsCategoryDataClass(): void
    {
        $resolver = new OptionsResolver();

        (new ArticleCategoryType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(ArticleCategory::class, $options['data_class']);
    }

    public function testBuildFormRegistersExpectedFieldsAndOptions(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $form = $factory->create(ArticleCategoryType::class, new ArticleCategory());

        $this->assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        $this->assertSame(120, $form->get('name')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('shortDescription')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('shortDescription')->getConfig()->getOption('required'));
        $this->assertSame(320, $form->get('shortDescription')->getConfig()->getOption('attr')['maxlength']);
        $this->assertSame(4, $form->get('shortDescription')->getConfig()->getOption('attr')['rows']);

        $this->assertInstanceOf(FormType::class, $form->get('titles')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('titles')->get('pl')->getConfig()->getType()->getInnerType());
        $this->assertSame(255, $form->get('titles')->get('pl')->getConfig()->getOption('attr')['maxlength']);
        $this->assertTrue($form->get('titles')->get('pl')->getConfig()->getOption('required'));

        $this->assertInstanceOf(TextType::class, $form->get('titles')->get('en')->getConfig()->getType()->getInnerType());
        $this->assertSame(255, $form->get('titles')->get('en')->getConfig()->getOption('attr')['maxlength']);
        $this->assertTrue($form->get('titles')->get('en')->getConfig()->getOption('required'));

        $this->assertInstanceOf(FormType::class, $form->get('descriptions')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextareaType::class, $form->get('descriptions')->get('pl')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('descriptions')->get('pl')->getConfig()->getOption('required'));
        $this->assertSame(1000, $form->get('descriptions')->get('pl')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('descriptions')->get('en')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('descriptions')->get('en')->getConfig()->getOption('required'));
        $this->assertSame(1000, $form->get('descriptions')->get('en')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextType::class, $form->get('icon')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('icon')->getConfig()->getOption('required'));
        $this->assertSame(255, $form->get('icon')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(EnumType::class, $form->get('status')->getConfig()->getType()->getInnerType());
    }
}
