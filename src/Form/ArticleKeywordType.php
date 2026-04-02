<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArticleKeyword;
use App\Enum\ArticleCategoryStatus;
use App\Enum\ArticleKeywordLanguage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArticleKeywordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('language', EnumType::class, [
                'class' => ArticleKeywordLanguage::class,
                'choice_label' => static fn (ArticleKeywordLanguage $language): string => $language->label(),
                'choice_attr' => static fn (ArticleKeywordLanguage $language): array => [
                    'data-i18n' => $language->translationKey(),
                ],
                'label' => 'Język',
                'label_attr' => ['data-i18n' => 'form_language'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('color', TextType::class, [
                'label' => 'Kolor',
                'label_attr' => ['data-i18n' => 'article_keyword_form_color'],
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'data-optional-color-value' => 'true',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Unikalna nazwa',
                'label_attr' => ['data-i18n' => 'article_keyword_form_name'],
                'empty_data' => '',
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'article_keyword_form_name_placeholder',
                    'placeholder' => 'Na przykład: php-8-4',
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => ArticleCategoryStatus::class,
                'choice_label' => static fn (ArticleCategoryStatus $status): string => $status->label(),
                'choice_attr' => static fn (ArticleCategoryStatus $status): array => [
                    'data-i18n' => $status->translationKey(),
                ],
                'label' => 'Stan',
                'label_attr' => ['data-i18n' => 'form_status'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleKeyword::class,
        ]);
    }
}
