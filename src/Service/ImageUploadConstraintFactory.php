<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ImageUploadConstraintFactory
{
    public function __construct(
        private readonly ?UploadLimitResolver $uploadLimitResolver = null,
        private readonly ?FileSizeFormatter $fileSizeFormatter = null,
        private readonly ?UserLanguageResolver $userLanguageResolver = null,
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
        return $this->buildTooLargeMessage(
            $this->resolveEffectiveLimit($applicationLimit),
        );
    }

    private function createImageConstraint(int $applicationLimit): Constraint
    {
        $applicationLimitMessage = $this->buildTooLargeMessage($applicationLimit);

        return new Callback([
            'callback' => function (mixed $value, ExecutionContextInterface $context) use ($applicationLimit, $applicationLimitMessage): void {
                if (!$value instanceof UploadedFile) {
                    return;
                }

                if (!$value->isValid()) {
                    return;
                }

                if ($value->getSize() > $applicationLimit) {
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
