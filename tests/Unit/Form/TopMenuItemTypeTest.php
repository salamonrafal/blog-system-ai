<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\TopMenuItem;
use App\Form\TopMenuItemType;
use App\Enum\TopMenuItemTargetType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

final class TopMenuItemTypeTest extends TestCase
{
    public function testConfigureOptionsSetsExpectedDefaults(): void
    {
        $resolver = new OptionsResolver();

        (new TopMenuItemType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(TopMenuItem::class, $options['data_class']);
        $this->assertArrayHasKey('pl', $options['translation_languages']);
        $this->assertArrayHasKey('en', $options['translation_languages']);
    }

    public function testBuildFormRegistersExpectedFields(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(TopMenuItemType::class, new TopMenuItem());

        $this->assertInstanceOf(FormType::class, $form->get('labels')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(TextType::class, $form->get('labels')->get('pl')->getConfig()->getType()->getInnerType());
        $this->assertContainsOnlyInstancesOf(NotBlank::class, [$form->get('labels')->get('pl')->getConfig()->getOption('constraints')[0]]);
        $this->assertContainsOnlyInstancesOf(Length::class, [$form->get('labels')->get('pl')->getConfig()->getOption('constraints')[1]]);

        $this->assertInstanceOf(EnumType::class, $form->get('targetType')->getConfig()->getType()->getInnerType());
        $this->assertContains(TopMenuItemTargetType::NONE, $form->get('targetType')->getConfig()->getOption('class')::cases());
        $this->assertInstanceOf(TextType::class, $form->get('externalUrl')->getConfig()->getType()->getInnerType());
        $this->assertSame(500, $form->get('externalUrl')->getConfig()->getOption('attr')['maxlength']);
        $this->assertInstanceOf(CheckboxType::class, $form->get('externalUrlOpenInNewWindow')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('articleCategory')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('article')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(ChoiceType::class, $form->get('parent')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(IntegerType::class, $form->get('position')->getConfig()->getType()->getInnerType());
        $this->assertInstanceOf(EnumType::class, $form->get('status')->getConfig()->getType()->getInnerType());
    }

    public function testParentChoiceLabelUsesConfiguredAdminLanguage(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();

        $parent = (new TopMenuItem())
            ->setLabel('pl', 'Kontakt')
            ->setLabel('en', 'Contact');

        $form = $factory->create(TopMenuItemType::class, new TopMenuItem(), [
            'admin_language' => 'en',
            'parent_items' => [$parent],
        ]);

        $choiceLabel = $form->get('parent')->getConfig()->getOption('choice_label');

        $this->assertIsCallable($choiceLabel);
        $this->assertSame('Contact', $choiceLabel($parent));
    }

    public function testChoicePlaceholdersUseConfiguredAdminLanguage(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();

        $form = $factory->create(TopMenuItemType::class, new TopMenuItem(), [
            'admin_language' => 'en',
        ]);

        $this->assertSame('Select a category', $form->get('articleCategory')->getConfig()->getOption('placeholder'));
        $this->assertSame('Select an article', $form->get('article')->getConfig()->getOption('placeholder'));
        $this->assertSame('Top level', $form->get('parent')->getConfig()->getOption('placeholder'));
    }
}
