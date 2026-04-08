<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\FileSizeFormatter;
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

class ArticleImportType extends AbstractType
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $applicationLimitMessage = $this->buildTooLargeMessage(self::MAX_FILE_SIZE);

        $builder->add('importFile', FileType::class, [
            'label' => 'Plik importu',
            'label_attr' => ['data-i18n' => 'admin_import_form_file'],
            'mapped' => false,
            'constraints' => [
                new NotNull([
                    'message' => 'validation_import_file_required',
                ]),
                new Callback([
                    'callback' => function (mixed $value, ExecutionContextInterface $context) use ($applicationLimitMessage): void {
                        if (!$value instanceof UploadedFile) {
                            return;
                        }

                        if ($value->getSize() > self::MAX_FILE_SIZE) {
                            $context->buildViolation($applicationLimitMessage)
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
        $resolver->setDefaults([
            'post_max_size_message' => $this->buildTooLargeMessage(
                $this->resolveEffectiveLimit(self::MAX_FILE_SIZE),
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
            sprintf('Plik importu nie może być większy niż %s.', $formattedLimit),
            sprintf('The import file cannot be larger than %s.', $formattedLimit),
        );
    }

    private function translate(string $polish, string $english): string
    {
        return $this->userLanguageResolver?->translate($polish, $english) ?? $polish;
    }
}
