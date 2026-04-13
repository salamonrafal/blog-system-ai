<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ArticleMarkupRenderer;
use PHPUnit\Framework\TestCase;

final class ArticleMarkupRendererTest extends TestCase
{
    public function testRendersHeadingsParagraphsAndInlineFormatting(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render("# Tytul\n\nTo jest **pogrubienie**, *kursywa*, ++podkreslenie++ i `kod`.");

        $this->assertStringContainsString('<h1 id="tytul">Tytul</h1>', $html);
        $this->assertStringContainsString('<strong>pogrubienie</strong>', $html);
        $this->assertStringContainsString('<em>kursywa</em>', $html);
        $this->assertStringContainsString('<u>podkreslenie</u>', $html);
        $this->assertStringContainsString('<code>kod</code>', $html);
    }

    public function testRendersListsQuotesLinksImagesAndCodeBlocks(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
## Sekcja

- punkt 1
- punkt 2

1. krok 1
2. krok 2

> Cytat testowy

[OpenAI](https://openai.com)
![Alt](https://example.com/test.png)

```php
echo 'hi';
```
TEXT);

        $this->assertStringContainsString('<ul><li>punkt 1</li><li>punkt 2</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>krok 1</li><li>krok 2</li></ol>', $html);
        $this->assertStringContainsString('<blockquote><p>Cytat testowy</p></blockquote>', $html);
        $this->assertStringContainsString('<a href="https://openai.com" target="_blank" rel="noopener noreferrer">OpenAI</a>', $html);
        $this->assertStringContainsString('<img src="https://example.com/test.png" alt="Alt" loading="lazy">', $html);
        $this->assertStringContainsString('<pre><code class="language-php">echo &#039;hi&#039;;</code></pre>', $html);
    }

    public function testRendersNestedListsWithChildren(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
- Backend
  - PHP
  - Symfony
- Frontend
  1. HTML
  2. CSS
TEXT);

        $this->assertStringContainsString('<ul><li>Backend<ul><li>PHP</li><li>Symfony</li></ul></li><li>Frontend<ol><li>HTML</li><li>CSS</li></ol></li></ul>', $html);
    }

    public function testRendersImageWithRootRelativePath(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](/uploads/media/2026/04/test-image.webp)');

        $this->assertStringContainsString('<img src="/uploads/media/2026/04/test-image.webp" alt="Obrazek" loading="lazy">', $html);
    }

    public function testDoesNotRenderImageForSchemeRelativeUrl(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](//example.com/test-image.webp)');

        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringContainsString('![Obrazek](//example.com/test-image.webp)', $html);
    }

    public function testDoesNotRenderImageForNonUploadRootRelativePath(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](/admin/secret/test-image.webp)');

        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringContainsString('![Obrazek](/admin/secret/test-image.webp)', $html);
    }

    public function testDoesNotRenderImageForUploadPathWithDotSegments(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](/uploads/../admin/secret/test-image.webp)');

        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringContainsString('![Obrazek](/uploads/../admin/secret/test-image.webp)', $html);
    }

    public function testDoesNotRenderImageForUploadPathWithEncodedDotSegments(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](/uploads/%2e%2e/admin/secret/test-image.webp)');

        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringContainsString('![Obrazek](/uploads/%2e%2e/admin/secret/test-image.webp)', $html);
    }

    public function testDoesNotRenderImageForUploadPathWithEncodedBackslashTraversal(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render('![Obrazek](/uploads/%5c..%5cadmin%5csecret/test-image.webp)');

        $this->assertStringNotContainsString('<img ', $html);
        $this->assertStringContainsString('![Obrazek](/uploads/%5c..%5cadmin%5csecret/test-image.webp)', $html);
    }

    public function testRendersAlignmentAndHeadingLevelSeven(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
####### Mikro naglowek

:::center
Wycentrowany akapit
:::
TEXT);

        $this->assertStringContainsString('<p class="article-heading-7">Mikro naglowek</p>', $html);
        $this->assertStringContainsString('<div class="article-align-center"><p>Wycentrowany akapit</p></div>', $html);
    }

    public function testRendersPreformattedBlock(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
:::pre
linia 1
  linia 2
<b>bez html</b>
:::
TEXT);

        $this->assertStringContainsString("<pre class=\"article-preformatted\">linia 1\n  linia 2\n&lt;b&gt;bez html&lt;/b&gt;</pre>", $html);
    }

    public function testRendersForcedLineBreakSeparatorAndTable(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
Pierwsza linia\
Druga linia

---

| Kolumna A | Kolumna B |
| --- | --- |
| `kod` | Wartosc |
TEXT);

        $this->assertStringContainsString('<p>Pierwsza linia<br>Druga linia</p>', $html);
        $this->assertStringContainsString('<hr>', $html);
        $this->assertStringContainsString('<div class="article-table-wrap"><table><thead><tr><th>Kolumna A</th><th>Kolumna B</th></tr></thead><tbody><tr><td><code>kod</code></td><td>Wartosc</td></tr></tbody></table></div>', $html);
    }

    public function testRendersForcedLineBreakInParagraphImmediatelyAfterTable(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
| Kolumna A | Kolumna B |
| --- | --- |
| Wartosc 1 | Wartosc 2 |
Po tabeli\
Druga linia
TEXT);

        $this->assertStringContainsString('<div class="article-table-wrap"><table><thead><tr><th>Kolumna A</th><th>Kolumna B</th></tr></thead><tbody><tr><td>Wartosc 1</td><td>Wartosc 2</td></tr></tbody></table></div>', $html);
        $this->assertStringContainsString('<p>Po tabeli<br>Druga linia</p>', $html);
    }

    public function testPreservesLeadingForcedLineBreaksAfterTable(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render(<<<'TEXT'
| Cecha | Monolit | Mikroserwisy |
| ------------ | --------- | -------------- |
| Deployment | Jeden | Wiele |
| Skalowanie | Całość | Per serwis |
| Złożoność | Niska | Wysoka |
| Wydajność | Wysoka | Niższa |

\
\
\
Architektura monolityczna.
TEXT);

        $this->assertStringContainsString('<div class="article-table-wrap"><table><thead><tr><th>Cecha</th><th>Monolit</th><th>Mikroserwisy</th></tr></thead><tbody><tr><td>Deployment</td><td>Jeden</td><td>Wiele</td></tr><tr><td>Skalowanie</td><td>Całość</td><td>Per serwis</td></tr><tr><td>Złożoność</td><td>Niska</td><td>Wysoka</td></tr><tr><td>Wydajność</td><td>Wysoka</td><td>Niższa</td></tr></tbody></table></div>', $html);
        $this->assertStringContainsString('<p><br><br><br>Architektura monolityczna.</p>', $html);
    }

    public function testEscapesRawHtml(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $html = $renderer->render("<script>alert('x')</script>\n\n**bezpieczne**");

        $this->assertStringContainsString('&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>bezpieczne</strong>', $html);
    }

    public function testExtractsTableOfContentsUpToHeadingLevelFourWithUniqueAnchors(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $toc = $renderer->extractTableOfContents(<<<'TEXT'
# Start
## Rozdzial
#### Detal
##### Pomijany
## Rozdzial

```md
## Ignorowany
```
TEXT);

        $this->assertSame([
            ['id' => 'start', 'level' => 1, 'title' => 'Start'],
            ['id' => 'rozdzial', 'level' => 2, 'title' => 'Rozdzial'],
            ['id' => 'detal', 'level' => 4, 'title' => 'Detal'],
            ['id' => 'rozdzial-2', 'level' => 2, 'title' => 'Rozdzial'],
        ], $toc);
    }

    public function testKeepsTableOfContentsAnchorsInSyncWithRenderedHeadingsWhenSkippedLevelsReuseTitle(): void
    {
        $renderer = new ArticleMarkupRenderer();
        $input = <<<'TEXT'
##### Intro
## Intro
####### Intro
## Intro
TEXT;

        $toc = $renderer->extractTableOfContents($input);
        $html = $renderer->render($input);

        $this->assertSame([
            ['id' => 'intro-2', 'level' => 2, 'title' => 'Intro'],
            ['id' => 'intro-3', 'level' => 2, 'title' => 'Intro'],
        ], $toc);
        $this->assertStringContainsString('<h5 id="intro">Intro</h5>', $html);
        $this->assertStringContainsString('<h2 id="intro-2">Intro</h2>', $html);
        $this->assertStringContainsString('<h2 id="intro-3">Intro</h2>', $html);
        $this->assertStringContainsString('<p class="article-heading-7">Intro</p>', $html);
    }

    public function testNormalizesInlineFormattingInTableOfContentsTitles(): void
    {
        $renderer = new ArticleMarkupRenderer();

        $toc = $renderer->extractTableOfContents(<<<'TEXT'
## **Bold** *Italic* `code` ++Underline++
TEXT);

        $this->assertSame([
            [
                'id' => 'bold-italic-code-underline',
                'level' => 2,
                'title' => 'Bold Italic code Underline',
            ],
        ], $toc);
    }

    public function testClosesIndentedCodeFenceAndContinuesParsingFollowingHeadings(): void
    {
        $renderer = new ArticleMarkupRenderer();
        $input = <<<'TEXT'
```php
echo 'one';
  ```
## Dalej
TEXT;

        $html = $renderer->render($input);
        $toc = $renderer->extractTableOfContents($input);

        $this->assertStringContainsString('<pre><code class="language-php">echo &#039;one&#039;;</code></pre>', $html);
        $this->assertStringContainsString('<h2 id="dalej">Dalej</h2>', $html);
        $this->assertSame([
            ['id' => 'dalej', 'level' => 2, 'title' => 'Dalej'],
        ], $toc);
    }

    public function testLevelSevenHeadingDoesNotConsumeAnchorNumbering(): void
    {
        $renderer = new ArticleMarkupRenderer();
        $input = <<<'TEXT'
####### Intro
## Intro
## Intro
TEXT;

        $html = $renderer->render($input);
        $toc = $renderer->extractTableOfContents($input);

        $this->assertStringContainsString('<p class="article-heading-7">Intro</p>', $html);
        $this->assertStringContainsString('<h2 id="intro">Intro</h2>', $html);
        $this->assertStringContainsString('<h2 id="intro-2">Intro</h2>', $html);
        $this->assertSame([
            ['id' => 'intro', 'level' => 2, 'title' => 'Intro'],
            ['id' => 'intro-2', 'level' => 2, 'title' => 'Intro'],
        ], $toc);
    }
}
