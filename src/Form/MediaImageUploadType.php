<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\FileSizeFormatter;
use App\Service\MediaImageSupport;
use App\Service\UploadLimitResolver;
use App\Service\UserLanguageResolver;
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
    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $applicationLimitMessage = $this->buildTooLargeMessage(MediaImageSupport::MAX_FILE_SIZE);

        $builder->add('imageFile', FileType::class, [
            'label' => 'Obrazek',
            'label_attr' => ['data-i18n' => 'admin_media_form_file'],
            'mapped' => false,
            'constraints' => [
                new NotNull([
                    'message' => 'validation_media_file_required',
                ]),
                new Callback([
                    'callback' => function (mixed $value, ExecutionContextInterface $context) use ($applicationLimitMessage): void {
                        if (!$value instanceof UploadedFile) {
                            return;
                        }

                        if ($value->getSize() > MediaImageSupport::MAX_FILE_SIZE) {
                            $context->buildViolation($applicationLimitMessage)
                                ->addViolation();

                            return;
                        }

                        $mimeType = MediaImageSupport::detectMimeType($value);

                        if (!MediaImageSupport::supportsFilename($value->getClientOriginalName())
                            || !MediaImageSupport::supportsMimeType($mimeType)
                            || !MediaImageSupport::filenameMatchesMimeType($value->getClientOriginalName(), $mimeType)
                        ) {
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
        $resolver->setDefaults([
            'post_max_size_message' => $this->buildTooLargeMessage(
                $this->resolveEffectiveLimit(MediaImageSupport::MAX_FILE_SIZE),
            ),
        ]);
    }

    private function resolveEffectiveLimit(int $applicationLimit): int
    {
        return ($this->uploadLimitResolver ?? new UploadLimitResolver())->resolveEffectiveLimit($applicationLimit) ?? $applicationLimit;
    }

    private function buildTooLargeMessage(int $limitBytes): string
    {
        $formattedLimit = ($this->fileSizeFormatter ?? new FileSizeFormatter())->format($limitBytes);

        return $this->translate(
            sprintf('Obrazek nie może być większy niż %s.', $formattedLimit),
            sprintf('The image cannot be larger than %s.', $formattedLimit),
        );
    }

    private function translate(string $polish, string $english): string
    {
        return $this->userLanguageResolver?->translate($polish, $english) ?? $polish;
    }
}
