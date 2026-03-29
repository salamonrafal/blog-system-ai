<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ArticleImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('importFile', FileType::class, [
            'label' => 'Plik importu',
            'label_attr' => ['data-i18n' => 'admin_import_form_file'],
            'mapped' => false,
            'constraints' => [
                new NotNull([
                    'message' => 'validation_import_file_required',
                ]),
                new Callback([
                    'callback' => static function (mixed $value, ExecutionContextInterface $context): void {
                        if (!$value instanceof UploadedFile) {
                            return;
                        }

                        if ($value->getSize() > 10 * 1024 * 1024) {
                            $context->buildViolation('validation_import_file_too_large')
                                ->addViolation();

                            return;
                        }

                        $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
                        if ('json' !== $extension) {
                            $context->buildViolation('validation_import_file_invalid')
                                ->addViolation();
                        }
                    },
                ]),
            ],
            'attr' => [
                'accept' => '.json',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
