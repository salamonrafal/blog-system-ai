<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageUploadConstraintFactory
{
    private const TOO_LARGE_MESSAGE_KEY = 'validation_media_file_too_large';
    private ?TranslationCatalogLoader $resolvedTranslationCatalogLoader = null;

    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
        private readonly ?TranslatorInterface $translator = null,
        private readonly ?TranslationCatalogLoader $translationCatalogLoader = null,
    ) {
    }

    /**
     * @return list<Constraint>
     */
    public function createRequiredImageConstraints(int $applicationLimit = MediaImageSupport::MAX_FILE_SIZE): array
    {
        return [
            new NotNull([
                'message' => 'validation_media_file_required',
            ]),
            $this->createImageConstraint($applicationLimit),
        ];
    }

    /**
     * @return list<Constraint>
     */
    public function createOptionalImageConstraints(int $applicationLimit = MediaImageSupport::MAX_FILE_SIZE): array
    {
        return [
            $this->createImageConstraint($applicationLimit),
        ];
    }

    public function buildPostMaxSizeMessage(int $applicationLimit = MediaImageSupport::MAX_FILE_SIZE): string
    {
        $parameters = $this->buildTooLargeMessageParameters($this->resolveEffectiveLimit($applicationLimit));

        if (null !== $this->translator) {
            return $this->translator->trans(
                self::TOO_LARGE_MESSAGE_KEY,
                $parameters,
                'validators',
                $this->userLanguageResolver?->getLanguage(),
            );
        }

        $messages = $this->translationCatalogLoader()->loadLanguageMessages(
            'validators',
            $this->userLanguageResolver?->getLanguage() ?? '',
        );

        return strtr($messages[self::TOO_LARGE_MESSAGE_KEY] ?? self::TOO_LARGE_MESSAGE_KEY, $parameters);
    }

    private function createImageConstraint(int $applicationLimit): Constraint
    {
        $applicationLimitMessageParameters = $this->buildTooLargeMessageParameters($applicationLimit);

        return new Callback([
            'callback' => function (mixed $value, ExecutionContextInterface $context) use ($applicationLimit, $applicationLimitMessageParameters): void {
                if (!$value instanceof UploadedFile) {
                    return;
                }

                if (!$value->isValid()) {
                    return;
                }

                if ($value->getSize() > $applicationLimit) {
                    $context->buildViolation(self::TOO_LARGE_MESSAGE_KEY)
                        ->setParameters($applicationLimitMessageParameters)
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
        ]);
    }

    private function resolveEffectiveLimit(int $applicationLimit): int
    {
        return ($this->uploadLimitResolver ?? new UploadLimitResolver())->resolveEffectiveLimit($applicationLimit) ?? $applicationLimit;
    }

    /**
     * @return array<string, string>
     */
    private function buildTooLargeMessageParameters(int $limitBytes): array
    {
        $formattedLimit = ($this->fileSizeFormatter ?? new FileSizeFormatter())->format($limitBytes);

        return [
            '{{ limit }}' => $formattedLimit,
        ];
    }

    private function translationCatalogLoader(): TranslationCatalogLoader
    {
        if (null !== $this->translationCatalogLoader) {
            return $this->translationCatalogLoader;
        }

        return $this->resolvedTranslationCatalogLoader ??= new TranslationCatalogLoader();
    }
}
