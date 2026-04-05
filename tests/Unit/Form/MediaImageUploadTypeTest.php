<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\MediaImageUploadType;
use App\Service\MediaImageSupport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

final class MediaImageUploadTypeTest extends TestCase
{
    public function testConfigureOptionsSetsNoUnexpectedDefaults(): void
    {
        $resolver = new OptionsResolver();

        (new MediaImageUploadType())->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayNotHasKey('data_class', $options);
    }

    public function testBuildFormRegistersImageFileField(): void
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $form = $factory->create(MediaImageUploadType::class);

        $field = $form->get('imageFile');

        $this->assertInstanceOf(FileType::class, $field->getConfig()->getType()->getInnerType());
        $this->assertFalse($field->getConfig()->getOption('mapped'));
        $this->assertSame(MediaImageSupport::acceptAttribute(), $field->getConfig()->getOption('attr')['accept']);
        $this->assertCount(2, $field->getConfig()->getOption('constraints'));
    }
}
