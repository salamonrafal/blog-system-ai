<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\BlogSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('blogTitle', TextType::class, [
                'label' => 'Tytuł bloga',
                'attr' => ['maxlength' => 255],
            ])
            ->add('homepageSeoDescription', TextareaType::class, [
                'label' => 'Opis strony głównej dla SEO',
                'attr' => ['rows' => 4, 'maxlength' => 320],
            ])
            ->add('homepageSocialImage', TextType::class, [
                'label' => 'Obrazek dla strony głównej',
                'attr' => ['maxlength' => 500],
            ])
            ->add('homepageSeoKeywords', TextareaType::class, [
                'label' => 'Słowa kluczowe SEO',
                'attr' => ['rows' => 3, 'maxlength' => 500],
            ])
            ->add('articlesPerPage', IntegerType::class, [
                'label' => 'Ilość artykułów na stronę',
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
                ],
            ])
            ->add('adminArticlesPerPage', IntegerType::class, [
                'label' => 'Ilość artykułów na stronę w panelu administracyjnym',
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogSettings::class,
        ]);
    }
}
