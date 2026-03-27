<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArticleCategory;
use App\Enum\ArticleCategoryStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'label_attr' => ['data-i18n' => 'category_form_name'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 120,
                    'data-i18n-placeholder' => 'category_form_name_placeholder',
                    'placeholder' => 'Na przykład: PHP, AI, Architektura',
                ],
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Krótki opis',
                'label_attr' => ['data-i18n' => 'category_form_short_description'],
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input article-editor-textarea',
                    'maxlength' => 320,
                    'rows' => 4,
                    'data-i18n-placeholder' => 'category_form_short_description_placeholder',
                    'placeholder' => 'Krótki opis, który pomoże rozpoznać przeznaczenie kategorii.',
                ],
            ])
            ->add('icon', TextType::class, [
                'label' => 'Ikona',
                'label_attr' => ['data-i18n' => 'category_form_icon'],
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'category_form_icon_placeholder',
                    'placeholder' => 'Na przykład: ph ph-tag lub /assets/img/icon.svg',
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

        $titlesForm = $builder->create('titles', FormType::class, [
            'mapped' => false,
        ]);

        $descriptionsForm = $builder->create('descriptions', FormType::class, [
            'mapped' => false,
        ]);

        foreach ($options['translation_languages'] as $language => $label) {
            $titlesForm->add($language, TextType::class, [
                'label' => 'Tytuł',
                'label_attr' => ['data-i18n' => 'form_title'],
                'required' => true,
                'data' => $category instanceof ArticleCategory ? $category->getTitle($language) ?? '' : '',
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'category_translation_title_placeholder',
                    'placeholder' => 'pl' === $language ? 'Na przykład: Programowanie w PHP' : 'For example: PHP Development',
                ],
            ]);

            $descriptionsForm->add($language, TextareaType::class, [
                'label' => 'Opis',
                'label_attr' => ['data-i18n' => 'category_form_description'],
                'required' => false,
                'data' => $category instanceof ArticleCategory ? $category->getDescription($language) : null,
                'attr' => [
                    'class' => 'article-editor-input article-editor-textarea',
                    'maxlength' => 1000,
                    'rows' => 4,
                    'data-i18n-placeholder' => 'category_translation_description_placeholder',
                    'placeholder' => 'pl' === $language
                        ? 'Opis kategorii widoczny dla polskiej wersji językowej.'
                        : 'Category description for the English version.',
                ],
            ]);
        }

        $builder
            ->add($titlesForm)
            ->add($descriptionsForm);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleCategory::class,
            'translation_languages' => [
                'pl' => 'Polski (PL)',
                'en' => 'English (EN)',
            ],
        ]);

        $resolver->setAllowedTypes('translation_languages', 'array');
    }
}
