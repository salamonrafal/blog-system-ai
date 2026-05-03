<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AnalyticsScript;
use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnalyticsScriptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pageName', TextType::class, [
                'label' => 'Identyfikator',
                'label_attr' => ['data-i18n' => 'analytics_script_form_page_name'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 120,
                    'data-i18n-placeholder' => 'analytics_script_form_page_name_placeholder',
                    'placeholder' => 'google_analytics',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'label_attr' => ['data-i18n' => 'analytics_script_form_name'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'maxlength' => 255,
                    'data-i18n-placeholder' => 'analytics_script_form_name_placeholder',
                    'placeholder' => 'Google Analytics',
                ],
            ])
            ->add('scope', EnumType::class, [
                'class' => AnalyticsScriptScope::class,
                'choice_label' => static fn (AnalyticsScriptScope $scope): string => $scope->label(),
                'choice_attr' => static fn (AnalyticsScriptScope $scope): array => [
                    'data-i18n' => $scope->translationKey(),
                ],
                'label' => 'Zakres stron',
                'label_attr' => ['data-i18n' => 'analytics_script_form_scope'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('placement', EnumType::class, [
                'class' => AnalyticsScriptPlacement::class,
                'choice_label' => static fn (AnalyticsScriptPlacement $placement): string => $placement->label(),
                'choice_attr' => static fn (AnalyticsScriptPlacement $placement): array => [
                    'data-i18n' => $placement->translationKey(),
                ],
                'label' => 'Miejsce wstrzyknięcia',
                'label_attr' => ['data-i18n' => 'analytics_script_form_placement'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-select',
                ],
            ])
            ->add('script', TextareaType::class, [
                'label' => 'Kod JavaScript',
                'label_attr' => ['data-i18n' => 'analytics_script_form_script'],
                'attr' => [
                    'class' => 'article-editor-input article-editor-code-input',
                    'rows' => 12,
                    'data-i18n-placeholder' => 'analytics_script_form_script_placeholder',
                    'placeholder' => '<script>...</script>',
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Pozycja',
                'label_attr' => ['data-i18n' => 'analytics_script_form_position'],
                'attr' => [
                    'class' => 'article-editor-input',
                    'min' => 0,
                    'data-i18n-placeholder' => 'analytics_script_form_position_placeholder',
                    'placeholder' => '0',
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Włączony',
                'label_attr' => ['data-i18n' => 'analytics_script_form_enabled'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnalyticsScript::class,
        ]);
    }
}
