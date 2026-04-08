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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

final class ArticleImportTypeTest extends TestCase
{
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
}
