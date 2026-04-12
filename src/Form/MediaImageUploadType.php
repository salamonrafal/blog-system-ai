<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\FileSizeFormatter;
use App\Service\ImageUploadConstraintFactory;
use App\Service\MediaImageSupport;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaImageUploadType extends AbstractType
{
    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
        private readonly ?ImageUploadConstraintFactory $imageUploadConstraintFactory = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFile', FileType::class, [
            'label' => 'Obrazek',
            'label_attr' => ['data-i18n' => 'admin_media_form_file'],
            'mapped' => false,
            'constraints' => $this->imageUploadConstraintFactory()->createRequiredImageConstraints(MediaImageSupport::MAX_FILE_SIZE),
            'attr' => [
                'accept' => MediaImageSupport::acceptAttribute(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'post_max_size_message' => $this->imageUploadConstraintFactory()->buildPostMaxSizeMessage(MediaImageSupport::MAX_FILE_SIZE),
        ]);
    }

    private function imageUploadConstraintFactory(): ImageUploadConstraintFactory
    {
        return $this->imageUploadConstraintFactory ?? new ImageUploadConstraintFactory(
            $this->uploadLimitResolver,
            $this->fileSizeFormatter,
            $this->userLanguageResolver,
        );
    }
}
