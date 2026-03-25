<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 180,
                    'autocomplete' => 'email',
                    'placeholder' => 'admin@example.com',
                ],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Imię i nazwisko',
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'autocomplete' => 'name',
                    'placeholder' => 'Jan Kowalski',
                ],
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Pseudonim',
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 120,
                    'placeholder' => 'jkowalski',
                ],
            ])
            ->add('shortBio', TextareaType::class, [
                'label' => 'Krótki opis',
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input article-editor-textarea',
                    'maxlength' => 500,
                    'rows' => 4,
                    'placeholder' => 'Kilka zdań o użytkowniku lub roli w zespole.',
                ],
            ])
            ->add('avatar', TextType::class, [
                'label' => 'Avatar',
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 500,
                    'placeholder' => 'https://... lub avatar.webp',
                ],
            ])
            ->add('isAdmin', CheckboxType::class, [
                'label' => 'Dostęp administratora',
                'mapped' => false,
                'required' => false,
                'data' => $options['is_admin'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Aktywne konto',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['password_required'] ? 'Hasło' : 'Nowe hasło',
                'mapped' => false,
                'required' => $options['password_required'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'autocomplete' => 'new-password',
                    'placeholder' => $options['password_required']
                        ? 'Ustaw hasło dla nowego użytkownika'
                        : 'Pozostaw puste, aby nie zmieniać',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
            'password_required' => false,
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('password_required', 'bool');
    }
}
