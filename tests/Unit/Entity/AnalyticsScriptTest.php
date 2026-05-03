<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AnalyticsScript;
use App\Enum\AnalyticsScriptPlacement;
use App\Enum\AnalyticsScriptScope;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AnalyticsScriptTest extends TestCase
{
    public function testAnalyticsScriptExposesAssignedValues(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName(' Google_Analytics ')
            ->setName(' Google Analytics ')
            ->setScope(AnalyticsScriptScope::ARTICLE)
            ->setPlacement(AnalyticsScriptPlacement::BODY_END)
            ->setScript(" <script>console.log('analytics');</script> ")
            ->setEnabled(false)
            ->setPosition(3);

        $this->assertSame('google_analytics', $script->getPageName());
        $this->assertSame('Google Analytics', $script->getName());
        $this->assertSame(AnalyticsScriptScope::ARTICLE, $script->getScope());
        $this->assertSame(AnalyticsScriptPlacement::BODY_END, $script->getPlacement());
        $this->assertSame("<script>console.log('analytics');</script>", $script->getScript());
        $this->assertFalse($script->isEnabled());
        $this->assertSame(3, $script->getPosition());
    }

    public function testSnippetMustContainScriptTag(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $script = (new AnalyticsScript())
            ->setPageName('invalid_snippet')
            ->setName('Invalid snippet')
            ->setScript("console.log('missing tag');");

        $violations = $validator->validate($script);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertContains('validation_analytics_script_snippet_script_tag_required', $messages);
    }

    public function testSnippetDoesNotTreatScriptPrefixAsScriptTag(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $script = (new AnalyticsScript())
            ->setPageName('invalid_script_prefix')
            ->setName('Invalid script prefix')
            ->setScript('<scripture>console.log("not a script tag");</scripture>');

        $violations = $validator->validate($script);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertContains('validation_analytics_script_snippet_script_tag_required', $messages);
    }

    public function testSnippetCannotCloseDocumentTags(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $script = (new AnalyticsScript())
            ->setPageName('bad_document_tag')
            ->setName('Bad document tag')
            ->setScript('<script></script></body>');

        $violations = $validator->validate($script);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertContains('validation_analytics_script_snippet_disallowed_document_tag', $messages);
    }

    public function testSnippetAllowsTagsThatOnlyStartLikeBlockedDocumentTags(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $script = (new AnalyticsScript())
            ->setPageName('safe_similar_tags')
            ->setName('Safe similar tags')
            ->setScript('<script>document.write("</header></bodyguard></htmlish>");</script>');

        $violations = $validator->validate($script);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertNotContains('validation_analytics_script_snippet_disallowed_document_tag', $messages);
    }

    public function testPageNameMustBeMachineReadable(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $script = (new AnalyticsScript())
            ->setPageName('Google Analytics!')
            ->setName('Google Analytics')
            ->setScript('<script></script>');

        $violations = $validator->validate($script);
        $messages = array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));

        $this->assertContains('validation_analytics_script_page_name_invalid', $messages);
    }
}
