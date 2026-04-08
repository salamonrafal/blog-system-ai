<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\ArticleImportType;
use App\Service\FileSizeFormatter;
use App\Service\UploadLimitResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

final class ArticleImportTypeTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/blog-system-ai-article-import-type-'.bin2hex(random_bytes(4));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testConfigureOptionsSetsNoUnexpectedDefaults(): void
    {
        $resolver = new OptionsResolver();

        (new ArticleImportType(
            new UploadLimitResolver(static fn (string $key): string|false => match ($key) {
                'upload_max_filesize' => '2M',
                'post_max_size' => '8M',
                default => false,
            }),
            new FileSizeFormatter(),
        ))->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayNotHasKey('data_class', $options);
        $this->assertSame('Plik importu nie może być większy niż 2.0 MB.', $options['post_max_size_message']);
    }

    public function testBuildFormRegistersImportFileField(): void
    {
        $validator = Validation::createValidator();
        $type = new ArticleImportType(
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
        $form = $factory->create(ArticleImportType::class);

        $field = $form->get('importFile');

        $this->assertInstanceOf(FileType::class, $field->getConfig()->getType()->getInnerType());
        $this->assertFalse($field->getConfig()->getOption('mapped'));
        $this->assertSame('.json', $field->getConfig()->getOption('attr')['accept']);
        $this->assertCount(2, $field->getConfig()->getOption('constraints'));
    }

    public function testValidationSkipsCustomChecksWhenUploadFailedInPhp(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new ArticleImportType())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(ArticleImportType::class);

        $sourcePath = $this->projectDir.'/too-large.json';
        file_put_contents($sourcePath, '{"invalid":true}');

        $uploadedFile = new UploadedFile($sourcePath, 'too-large.json', 'application/json', \UPLOAD_ERR_INI_SIZE, true);
        $constraints = $form->get('importFile')->getConfig()->getOption('constraints');
        $violations = $validator->validate($uploadedFile, $constraints);

        $this->assertCount(0, $violations);
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
