<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\MediaImageSupport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MediaImageUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFile', FileType::class, [
            'label' => 'Obrazek',
            'label_attr' => ['data-i18n' => 'admin_media_form_file'],
            'mapped' => false,
            'constraints' => [
                new NotNull([
                    'message' => 'validation_media_file_required',
                ]),
                new Callback([
                    'callback' => static function (mixed $value, ExecutionContextInterface $context): void {
                        if (!$value instanceof UploadedFile) {
                            return;
                        }

                        if ($value->getSize() > MediaImageSupport::MAX_FILE_SIZE) {
                            $context->buildViolation('validation_media_file_too_large')
                                ->addViolation();

                            return;
                        }

                        $mimeType = MediaImageSupport::detectMimeType($value);

                        if (!MediaImageSupport::supportsFilename($value->getClientOriginalName()) || !MediaImageSupport::supportsMimeType($mimeType)) {
                            $context->buildViolation('validation_media_file_invalid')
                                ->addViolation();
                        }
                    },
                ]),
            ],
            'attr' => [
                'accept' => MediaImageSupport::acceptAttribute(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
