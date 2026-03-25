<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\ArticleImportType;
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

        (new ArticleImportType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayNotHasKey('data_class', $options);
    }

    public function testBuildFormRegistersImportFileField(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
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
