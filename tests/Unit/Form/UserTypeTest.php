<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\User;
use App\Form\UserType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserTypeTest extends TestCase
{
    public function testConfigureOptionsSetsUserDataClassAndAdminFlag(): void
    {
        $resolver = new OptionsResolver();

        (new UserType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertSame(User::class, $options['data_class']);
        $this->assertFalse($options['is_admin']);
    }

    public function testBuildFormRegistersExpectedFieldsAndOptions(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $form = $factory->create(UserType::class, new User(), ['is_admin' => true]);

        $this->assertInstanceOf(EmailType::class, $form->get('email')->getConfig()->getType()->getInnerType());
        $this->assertSame(180, $form->get('email')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextType::class, $form->get('fullName')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('fullName')->getConfig()->getOption('required'));
        $this->assertSame(255, $form->get('fullName')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextType::class, $form->get('nickname')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('nickname')->getConfig()->getOption('required'));
        $this->assertSame(120, $form->get('nickname')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(TextareaType::class, $form->get('shortBio')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('shortBio')->getConfig()->getOption('required'));
        $this->assertSame(500, $form->get('shortBio')->getConfig()->getOption('attr')['maxlength']);
        $this->assertSame(4, $form->get('shortBio')->getConfig()->getOption('attr')['rows']);

        $this->assertInstanceOf(TextType::class, $form->get('avatar')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('avatar')->getConfig()->getOption('required'));
        $this->assertSame(500, $form->get('avatar')->getConfig()->getOption('attr')['maxlength']);

        $this->assertInstanceOf(CheckboxType::class, $form->get('isAdmin')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('isAdmin')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('isAdmin')->getConfig()->getOption('mapped'));
        $this->assertTrue($form->get('isAdmin')->getData());

        $this->assertInstanceOf(CheckboxType::class, $form->get('isActive')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('isActive')->getConfig()->getOption('required'));

        $this->assertInstanceOf(PasswordType::class, $form->get('plainPassword')->getConfig()->getType()->getInnerType());
        $this->assertFalse($form->get('plainPassword')->getConfig()->getOption('required'));
        $this->assertFalse($form->get('plainPassword')->getConfig()->getOption('mapped'));
    }

    public function testBuildFormCanRequirePasswordForUserCreation(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $form = $factory->create(UserType::class, new User(), ['password_required' => true]);

        $this->assertTrue($form->get('plainPassword')->getConfig()->getOption('required'));
        $this->assertSame('Hasło', $form->get('plainPassword')->getConfig()->getOption('label'));
    }
}
