<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\TopMenuItem;
use App\Enum\TopMenuItemStatus;
use App\Enum\TopMenuItemTargetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TopMenuItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $menuItem = $builder->getData();

        $builder
            ->add('targetType', EnumType::class, [
                'class' => TopMenuItemTargetType::class,
                'choice_label' => static fn (TopMenuItemTargetType $targetType): string => $targetType->label(),
                'choice_attr' => static fn (TopMenuItemTargetType $targetType): array => [
                    'data-i18n' => $targetType->translationKey(),
                ],
                'label' => 'Typ odnośnika',
                'label_attr' => ['data-i18n' => 'top_menu_form_target_type'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                    'data-menu-target-input' => '',
                ],
            ])
            ->add('externalUrl', TextType::class, [
                'label' => 'Adres URL',
                'label_attr' => ['data-i18n' => 'top_menu_form_external_url'],
                'required' => false,
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 500,
                    'data-i18n-placeholder' => 'top_menu_form_external_url_placeholder',
                    'placeholder' => 'https://example.com',
                ],
            ])
            ->add('articleCategory', ChoiceType::class, [
                'label' => 'Kategoria artykułów',
                'label_attr' => ['data-i18n' => 'top_menu_form_article_category'],
                'required' => false,
                'placeholder' => 'Wybierz kategorię',
                'choice_translation_domain' => false,
                'choices' => $options['article_categories'],
                'choice_label' => static fn (ArticleCategory $category): string => $category->getName(),
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('article', ChoiceType::class, [
                'label' => 'Artykuł',
                'label_attr' => ['data-i18n' => 'top_menu_form_article'],
                'required' => false,
                'placeholder' => 'Wybierz artykuł',
                'choice_translation_domain' => false,
                'choices' => $options['articles'],
                'choice_label' => static fn (Article $article): string => sprintf('%s (%s)', $article->getTitle(), strtoupper($article->getLanguage()->value)),
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('parent', ChoiceType::class, [
                'label' => 'Element nadrzędny',
                'label_attr' => ['data-i18n' => 'top_menu_form_parent'],
                'required' => false,
                'placeholder' => 'Poziom główny',
                'choice_translation_domain' => false,
                'choices' => $options['parent_items'],
                'choice_label' => static fn (TopMenuItem $parent): string => $parent->getLocalizedLabel('pl'),
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Pozycja',
                'label_attr' => ['data-i18n' => 'top_menu_form_position'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'min' => 0,
                    'data-i18n-placeholder' => 'top_menu_form_position_placeholder',
                    'placeholder' => '0',
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => TopMenuItemStatus::class,
                'choice_label' => static fn (TopMenuItemStatus $status): string => $status->label(),
                'choice_attr' => static fn (TopMenuItemStatus $status): array => [
                    'data-i18n' => $status->translationKey(),
                ],
                'label' => 'Stan',
                'label_attr' => ['data-i18n' => 'form_status'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ]);

        $labelsForm = $builder->create('labels', FormType::class, [
            'mapped' => false,
        ]);

        foreach ($options['translation_languages'] as $language => $label) {
            $labelsForm->add($language, TextType::class, [
                'label' => 'Nazwa elementu',
                'label_attr' => ['data-i18n' => 'top_menu_form_label'],
                'required' => true,
                'data' => $menuItem instanceof TopMenuItem ? $menuItem->getLabel($language) ?? '' : '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'validation_top_menu_label_required',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'validation_top_menu_label_too_long',
                    ]),
                ],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'placeholder' => 'pl' === $language ? 'Na przykład: Kontakt' : 'For example: Contact',
                ],
            ]);
        }

        $builder->add($labelsForm);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TopMenuItem::class,
            'translation_languages' => [
                'pl' => 'Polski (PL)',
                'en' => 'English (EN)',
            ],
            'parent_items' => [],
            'article_categories' => [],
            'articles' => [],
        ]);

        $resolver->setAllowedTypes('translation_languages', 'array');
        $resolver->setAllowedTypes('parent_items', 'array');
        $resolver->setAllowedTypes('article_categories', 'array');
        $resolver->setAllowedTypes('articles', 'array');
    }
}
