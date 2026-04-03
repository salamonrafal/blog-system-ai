<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Article;
use App\Entity\ArticleCategory;
use App\Entity\ArticleKeyword;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $article = $builder->getData();
        $assignedKeywords = $article instanceof Article ? iterator_to_array($article->getKeywords()) : [];
        $assignedKeywordIds = array_fill_keys(
            array_values(array_filter(
                array_map(
                    static fn (ArticleKeyword $keyword): ?int => $keyword->getId(),
                    $assignedKeywords,
                ),
                static fn (?int $keywordId): bool => null !== $keywordId,
            )),
            true,
        );
        $assignedKeywordObjectHashes = array_fill_keys(
            array_map(
                static fn (ArticleKeyword $keyword): string => spl_object_hash($keyword),
                array_filter(
                    $assignedKeywords,
                    static fn (ArticleKeyword $keyword): bool => null === $keyword->getId(),
                ),
            ),
            true,
        );

        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'label_attr' => ['data-i18n' => 'form_title'],
                'attr' => ['maxlength' => 255],
            ])
            ->add('language', EnumType::class, [
                'class' => ArticleLanguage::class,
                'choice_label' => static fn (ArticleLanguage $language): string => $language->label(),
                'label' => 'Language',
                'label_attr' => ['data-i18n' => 'form_language'],
                'choice_attr' => static fn (ArticleLanguage $language): array => [
                    'data-i18n' => match ($language) {
                        ArticleLanguage::PL => 'article_language_pl',
                        ArticleLanguage::EN => 'article_language_en',
                    },
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Short summary',
                'label_attr' => ['data-i18n' => 'form_excerpt'],
                'required' => false,
                'attr' => ['rows' => 4, 'maxlength' => 320],
            ])
            ->add('headlineImageEnabled', CheckboxType::class, [
                'label' => 'Enable headline image',
                'label_attr' => ['data-i18n' => 'form_headline_image_enabled'],
                'required' => false,
            ])
            ->add('headlineImage', TextType::class, [
                'label' => 'Headline image',
                'label_attr' => ['data-i18n' => 'form_headline_image'],
                'required' => false,
                'attr' => ['maxlength' => 500],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'label_attr' => ['data-i18n' => 'form_content'],
                'attr' => ['rows' => 16],
            ])
            ->add('status', EnumType::class, [
                'class' => ArticleStatus::class,
                'choice_label' => static fn (ArticleStatus $status): string => $status->label(),
                'label' => 'Status',
                'label_attr' => ['data-i18n' => 'form_status'],
                'choice_attr' => static fn (ArticleStatus $status): array => [
                    'data-i18n' => match ($status) {
                        ArticleStatus::DRAFT => 'article_status_draft',
                        ArticleStatus::REVIEW => 'article_status_review',
                        ArticleStatus::PUBLISHED => 'article_status_published',
                        ArticleStatus::ARCHIVED => 'article_status_archived',
                    },
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'label_attr' => ['data-i18n' => 'category_form_name'],
                'required' => false,
                'placeholder' => '---',
                'choice_translation_domain' => false,
                'choices' => $options['categories'],
                'choice_label' => static fn (ArticleCategory $category): string => $category->getName(),
            ])
            ->add('keywords', ChoiceType::class, [
                'label' => 'Keywords',
                'label_attr' => ['data-i18n' => 'article_form_keywords'],
                'required' => false,
                'multiple' => true,
                'by_reference' => false,
                'choice_translation_domain' => false,
                'choices' => $options['keywords'],
                'choice_value' => static fn (?ArticleKeyword $keyword): ?string => null !== $keyword?->getId()
                    ? (string) $keyword->getId()
                    : null,
                'choice_label' => static fn (ArticleKeyword $keyword): string => sprintf(
                    '%s (%s)',
                    $keyword->getName(),
                    $keyword->getLanguage()->label(),
                ),
                'choice_attr' => static fn (ArticleKeyword $keyword): array => array_filter([
                    'data-keyword-language' => $keyword->getLanguage()->value,
                    'data-keyword-name' => $keyword->getName(),
                    'data-keyword-scope-key' => $keyword->getLanguage()->translationKey(),
                    'disabled' => !$keyword->isActive()
                        && !isset($assignedKeywordIds[(int) $keyword->getId()])
                        && !isset($assignedKeywordObjectHashes[spl_object_hash($keyword)])
                        ? 'disabled'
                        : null,
                ], static fn (mixed $value): bool => null !== $value),
                'attr' => [
                    'class' => 'article-editor-input',
                ],
            ])
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Publish date',
                'label_attr' => ['data-i18n' => 'form_publish_date'],
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'model_timezone' => 'UTC',
                'view_timezone' => 'UTC',
                'invalid_message' => 'validation_article_published_at_invalid',
            ]);

        $builder->get('keywords')->addModelTransformer(new CallbackTransformer(
            static fn (mixed $keywords): array => $keywords instanceof \Traversable
                ? iterator_to_array($keywords)
                : (is_array($keywords) ? $keywords : []),
            static fn (mixed $keywords): mixed => $keywords,
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'categories' => [],
            'keywords' => [],
        ]);

        $resolver->setAllowedTypes('categories', 'array');
        $resolver->setAllowedTypes('keywords', 'array');
    }
}
