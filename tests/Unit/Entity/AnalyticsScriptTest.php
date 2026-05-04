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
        $script = (new AnalyticsScript())
            ->setPageName('invalid_snippet')
            ->setName('Invalid snippet')
            ->setScript("console.log('missing tag');");

        $this->assertContains('validation_analytics_script_snippet_script_tag_required', self::validationMessages($script));
    }

    public function testSnippetDoesNotTreatScriptPrefixAsScriptTag(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('invalid_script_prefix')
            ->setName('Invalid script prefix')
            ->setScript('<scripture>console.log("not a script tag");</scripture>');

        $this->assertContains('validation_analytics_script_snippet_script_tag_required', self::validationMessages($script));
    }

    public function testSnippetMustContainClosingScriptTag(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('missing_closing_script')
            ->setName('Missing closing script')
            ->setScript('<script>console.log("missing close");');

        $this->assertContains(
            'validation_analytics_script_snippet_script_tag_closing_required',
            self::validationMessages($script),
        );
    }

    public function testSnippetMustCloseEveryScriptTag(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('missing_second_closing_script')
            ->setName('Missing second closing script')
            ->setScript('<script>console.log("first");</script><script>console.log("second");');

        $this->assertContains(
            'validation_analytics_script_snippet_script_tag_closing_required',
            self::validationMessages($script),
        );
    }

    public function testSnippetCannotContainNonScriptMarkupAroundScriptTag(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('wrapped_script')
            ->setName('Wrapped script')
            ->setScript('<div class="tracker"><script>console.log("analytics");</script></div>');

        $this->assertContains(
            'validation_analytics_script_snippet_only_script_tags',
            self::validationMessages($script),
        );
    }

    public function testSnippetAllowsMultipleCompleteScriptTagsWithWhitespace(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('multiple_scripts')
            ->setName('Multiple scripts')
            ->setScript(<<<'HTML'
<script src="https://example.com/analytics.js"></script>

<script>
window.analytics = true;
</script>
HTML);

        $messages = self::validationMessages($script);

        $this->assertNotContains('validation_analytics_script_snippet_only_script_tags', $messages);
        $this->assertNotContains('validation_analytics_script_snippet_script_tag_closing_required', $messages);
    }

    public function testSnippetAllowsOpeningScriptTagLiteralInsideJavaScript(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('script_tag_literal')
            ->setName('Script tag literal')
            ->setScript('<script>console.log("<script>");</script>');

        $messages = self::validationMessages($script);

        $this->assertNotContains('validation_analytics_script_snippet_script_tag_closing_required', $messages);
        $this->assertNotContains('validation_analytics_script_snippet_only_script_tags', $messages);
    }

    public function testSnippetCannotContainRawClosingScriptTagLiteralInsideJavaScript(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('closing_script_tag_literal')
            ->setName('Closing script tag literal')
            ->setScript('<script>console.log("</script>");</script>');

        $this->assertContains(
            'validation_analytics_script_snippet_script_tag_literal_disallowed',
            self::validationMessages($script),
        );
    }

    public function testSnippetAllowsEscapedClosingScriptTagLiteralInsideJavaScript(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('escaped_closing_script_tag_literal')
            ->setName('Escaped closing script tag literal')
            ->setScript('<script>console.log("<\/script>");</script>');

        $messages = self::validationMessages($script);

        $this->assertNotContains('validation_analytics_script_snippet_script_tag_literal_disallowed', $messages);
        $this->assertNotContains('validation_analytics_script_snippet_only_script_tags', $messages);
    }

    public function testSnippetCannotContainDocumentClosingTagsOutsideScriptTag(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('bad_document_tag')
            ->setName('Bad document tag')
            ->setScript('<script></script></body>');

        $this->assertContains('validation_analytics_script_snippet_only_script_tags', self::validationMessages($script));
    }

    public function testSnippetAllowsDocumentClosingTagLiteralsInsideJavaScript(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('document_tag_literals')
            ->setName('Document tag literals')
            ->setScript('<script>console.log("</head></body></html>"); /* </body> */</script>');

        $messages = self::validationMessages($script);

        $this->assertNotContains('validation_analytics_script_snippet_only_script_tags', $messages);
        $this->assertNotContains('validation_analytics_script_snippet_script_tag_closing_required', $messages);
    }

    public function testPageNameMustBeMachineReadable(): void
    {
        $script = (new AnalyticsScript())
            ->setPageName('Google Analytics!')
            ->setName('Google Analytics')
            ->setScript('<script></script>');

        $this->assertContains('validation_analytics_script_page_name_invalid', self::validationMessages($script));
    }

    /**
     * @return list<string>
     */
    private static function validationMessages(AnalyticsScript $script): array
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($script);

        return array_map(static fn ($violation): string => $violation->getMessage(), iterator_to_array($violations));
    }
}
