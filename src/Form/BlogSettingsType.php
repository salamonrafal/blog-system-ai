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
            ->add('appUrl', TextType::class, [
                'label' => 'URL aplikacji',
                'label_attr' => ['data-i18n' => 'blog_settings_app_url'],
                'attr' => [
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'blog_settings_app_url_placeholder',
                ],
            ])
            ->add('blogTitle', TextType::class, [
                'label' => 'Tytuł bloga',
                'label_attr' => ['data-i18n' => 'blog_settings_blog_title'],
                'attr' => [
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'blog_settings_blog_title_placeholder',
                ],
            ])
            ->add('preferenceCookieDomainOverride', TextType::class, [
                'label' => 'Domena cookie preferencji',
                'required' => false,
                'empty_data' => '',
                'label_attr' => ['data-i18n' => 'blog_settings_preference_cookie_domain'],
                'attr' => [
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'blog_settings_preference_cookie_domain_placeholder',
                ],
            ])
            ->add('homepageSeoDescription', TextareaType::class, [
                'label' => 'Opis strony głównej dla SEO',
                'label_attr' => ['data-i18n' => 'blog_settings_homepage_seo_description'],
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 320,
                    'data-i18n-placeholder' => 'blog_settings_homepage_seo_description_placeholder',
                ],
            ])
            ->add('homepageSocialImage', TextType::class, [
                'label' => 'Obrazek dla strony głównej',
                'label_attr' => ['data-i18n' => 'blog_settings_homepage_social_image'],
                'attr' => [
                    'maxlength' => 500,
                    'data-i18n-placeholder' => 'blog_settings_homepage_social_image_placeholder',
                ],
            ])
            ->add('homepageSeoKeywords', TextareaType::class, [
                'label' => 'Słowa kluczowe SEO',
                'label_attr' => ['data-i18n' => 'blog_settings_homepage_seo_keywords'],
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 500,
                    'data-i18n-placeholder' => 'blog_settings_homepage_seo_keywords_placeholder',
                ],
            ])
            ->add('articlesPerPage', IntegerType::class, [
                'label' => 'Ilość artykułów na stronę',
                'label_attr' => ['data-i18n' => 'blog_settings_articles_per_page'],
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
                    'data-i18n-placeholder' => 'blog_settings_articles_per_page_placeholder',
                ],
            ])
            ->add('adminListingItemsPerPage', IntegerType::class, [
                'label' => 'Ilość elementów na stronę w panelu administracyjnym',
                'label_attr' => ['data-i18n' => 'blog_settings_admin_listing_items_per_page'],
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
                    'data-i18n-placeholder' => 'blog_settings_admin_listing_items_per_page_placeholder',
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
