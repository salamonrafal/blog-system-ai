<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Article;
use App\Enum\ArticleLanguage;
use App\Enum\ArticleStatus;
use Symfony\Component\Form\AbstractType;
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
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Publish date',
                'label_attr' => ['data-i18n' => 'form_publish_date'],
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'validation_article_published_at_invalid',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
