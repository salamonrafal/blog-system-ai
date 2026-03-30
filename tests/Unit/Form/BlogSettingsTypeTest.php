<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\BlogSettings;
use App\Form\BlogSettingsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BlogSettingsTypeTest extends TestCase
{
    public function testConfigureOptionsSetsBlogSettingsDataClass(): void
    {
        $resolver = new OptionsResolver();

        (new BlogSettingsType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(BlogSettings::class, $options['data_class']);
    }

    public function testBuildFormRegistersExpectedFieldsAndConstraints(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $form = $factory->create(BlogSettingsType::class, new BlogSettings());

        $this->assertInstanceOf(TextType::class, $form->get('appUrl')->getConfig()->getType()->getInnerType());
        $this->assertSame(255, $form->get('appUrl')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextType::class, $form->get('blogTitle')->getConfig()->getType()->getInnerType());
        $this->assertSame(255, $form->get('blogTitle')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('homepageSeoDescription')->getConfig()->getType()->getInnerType());
        $this->assertSame(4, $form->get('homepageSeoDescription')->getConfig()->getOption('attr')['rows']);
        $this->assertSame(320, $form->get('homepageSeoDescription')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextType::class, $form->get('homepageSocialImage')->getConfig()->getType()->getInnerType());
        $this->assertSame(500, $form->get('homepageSocialImage')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('homepageSeoKeywords')->getConfig()->getType()->getInnerType());
        $this->assertSame(3, $form->get('homepageSeoKeywords')->getConfig()->getOption('attr')['rows']);
        $this->assertSame(500, $form->get('homepageSeoKeywords')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(IntegerType::class, $form->get('articlesPerPage')->getConfig()->getType()->getInnerType());
        $this->assertSame(1, $form->get('articlesPerPage')->getConfig()->getOption('attr')['min']);
        $this->assertSame(1, $form->get('articlesPerPage')->getConfig()->getOption('attr')['step']);
        $this->assertSame('numeric', $form->get('articlesPerPage')->getConfig()->getOption('attr')['inputmode']);

        $this->assertInstanceOf(IntegerType::class, $form->get('adminListingItemsPerPage')->getConfig()->getType()->getInnerType());
        $this->assertSame(1, $form->get('adminListingItemsPerPage')->getConfig()->getOption('attr')['min']);
        $this->assertSame(1, $form->get('adminListingItemsPerPage')->getConfig()->getOption('attr')['step']);
        $this->assertSame('numeric', $form->get('adminListingItemsPerPage')->getConfig()->getOption('attr')['inputmode']);
    }
}
