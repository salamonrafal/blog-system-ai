<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Service\FileSizeFormatter;
use App\Service\ImageUploadConstraintFactory;
use App\Service\MediaImageSupport;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
        private readonly ?ImageUploadConstraintFactory $imageUploadConstraintFactory = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => ['data-i18n' => 'login_email'],
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
                'label_attr' => ['data-i18n' => 'user_form_full_name'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'autocomplete' => 'name',
                    'placeholder' => 'Jan Kowalski',
                    'data-i18n-placeholder' => 'user_form_full_name_placeholder',
                ],
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Pseudonim',
                'required' => false,
                'label_attr' => ['data-i18n' => 'user_form_nickname'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 120,
                    'placeholder' => 'jkowalski',
                    'data-i18n-placeholder' => 'user_form_nickname_placeholder',
                ],
            ])
            ->add('shortBio', TextareaType::class, [
                'label' => 'Krótki opis',
                'required' => false,
                'label_attr' => ['data-i18n' => 'user_form_short_bio'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-textarea',
                    'maxlength' => 500,
                    'rows' => 4,
                    'placeholder' => 'Kilka zdań o użytkowniku lub roli w zespole.',
                    'data-i18n-placeholder' => 'user_form_short_bio_placeholder',
                ],
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Avatar',
                'required' => false,
                'label_attr' => ['data-i18n' => 'user_form_avatar'],
                'mapped' => false,
                'constraints' => $this->imageUploadConstraintFactory()->createOptionalImageConstraints(MediaImageSupport::MAX_FILE_SIZE),
                'attr' => [
                    'accept' => MediaImageSupport::acceptAttribute(),
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
                'label_attr' => ['data-i18n' => $options['password_required'] ? 'login_password' : 'user_form_new_password'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'autocomplete' => 'new-password',
                    'placeholder' => $options['password_required']
                        ? 'Ustaw hasło dla nowego użytkownika'
                        : 'Pozostaw puste, aby nie zmieniać',
                    'data-i18n-placeholder' => $options['password_required']
                        ? 'user_form_password_placeholder_required'
                        : 'user_form_password_placeholder_optional',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
            'password_required' => false,
            'post_max_size_message' => $this->imageUploadConstraintFactory()->buildPostMaxSizeMessage(MediaImageSupport::MAX_FILE_SIZE),
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('password_required', 'bool');
    }

    private function imageUploadConstraintFactory(): ImageUploadConstraintFactory
    {
        return $this->imageUploadConstraintFactory ?? new ImageUploadConstraintFactory(
            $this->uploadLimitResolver,
            $this->fileSizeFormatter,
            $this->userLanguageResolver,
        );
    }
}
