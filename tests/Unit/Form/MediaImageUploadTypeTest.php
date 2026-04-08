<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Service\FileSizeFormatter;
use App\Form\MediaImageUploadType;
use App\Service\MediaImageSupport;
use App\Service\UploadLimitResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class MediaImageUploadTypeTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-media-image-upload-type-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testConfigureOptionsSetsNoUnexpectedDefaults(): void
    {
        $resolver = new OptionsResolver();

        (new MediaImageUploadType(
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            new FileSizeFormatter(),
        ))->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayNotHasKey('data_class', $options);
        $this->assertSame('Obrazek nie może być większy niż 2.0 MB.', $options['post_max_size_message']);
    }

    public function testBuildFormRegistersImageFileField(): void
    {
        $validator = Validation::createValidator();
        $type = new MediaImageUploadType(
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            new FileSizeFormatter(),
        );
        $factory = Forms::createFormFactoryBuilder()
            ->addType($type)
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(MediaImageUploadType::class);

        $field = $form->get('imageFile');

        $this->assertInstanceOf(FileType::class, $field->getConfig()->getType()->getInnerType());
        $this->assertFalse($field->getConfig()->getOption('mapped'));
        $this->assertSame(MediaImageSupport::acceptAttribute(), $field->getConfig()->getOption('attr')['accept']);
        $this->assertCount(2, $field->getConfig()->getOption('constraints'));
    }

    public function testValidationRejectsSpoofedClientMimeTypeForNonImagePayload(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new MediaImageUploadType())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(MediaImageUploadType::class);

        $sourcePath = $this->projectDir.'/fake-image.jpg';
        file_put_contents($sourcePath, 'not-an-image');

        $uploadedFile = new UploadedFile($sourcePath, 'fake-image.jpg', 'image/jpeg', null, true);
        $constraints = $form->get('imageFile')->getConfig()->getOption('constraints');
        $violations = $validator->validate($uploadedFile, $constraints);

        $this->assertCount(1, $violations);
        $this->assertSame('validation_media_file_invalid', $violations[0]->getMessage());
    }

    public function testValidationRejectsMismatchedFilenameExtensionForDetectedMimeType(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new MediaImageUploadType())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(MediaImageUploadType::class);

        $sourcePath = $this->projectDir.'/fake-jpg.webp';
        file_put_contents($sourcePath, base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAUAmJaACdLoB+AADsAD+8ut//NgVzXPv9//S4P0uD9Lg/9KQAAA='));

        $uploadedFile = new UploadedFile($sourcePath, 'fake-jpg.jpg', 'image/jpeg', null, true);
        $constraints = $form->get('imageFile')->getConfig()->getOption('constraints');
        $violations = $validator->validate($uploadedFile, $constraints);

        $this->assertCount(1, $violations);
        $this->assertSame('validation_media_file_invalid', $violations[0]->getMessage());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = $path.'/'.$item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);

                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
