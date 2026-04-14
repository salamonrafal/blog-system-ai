<?php

declare(strict_types=1);

namespace App\Service;

final class ArticleMarkupRenderer
{
    private const LINE_BREAK_TOKEN = '@@ARTICLE_LINE_BREAK@@';
    /**
     * Keep only the last parsed document to avoid unbounded growth on shared service instances.
     *
     * @var array{input: string, document: array{html: string, headings: list<array{id: string, level: int, title: string}>}}|null
     */
    private ?array $documentCache = null;

    /**
     * @return list<array{id: string, level: int, title: string}>
     */
    public function extractTableOfContents(string $input, int $maxLevel = 4): array
    {
        $normalized = self::normalizeInput($input);

        if ('' === $normalized) {
            return [];
        }

        return array_values(array_filter(
            $this->getParsedDocument($normalized)['headings'],
            static fn (array $heading): bool => $heading['level'] <= $maxLevel,
        ));
    }

    public function render(string $input): string
    {
        $normalized = self::normalizeInput($input);

        if ('' === $normalized) {
            return '';
        }

        return $this->getParsedDocument($normalized)['html'];
    }

    private static function normalizeInput(string $input): string
    {
        return str_replace(["\r\n", "\r"], "\n", trim($input));
    }

    /**
     * @return array{html: string, headings: list<array{id: string, level: int, title: string}>}
     */
    private function getParsedDocument(string $normalized): array
    {
        if (null !== $this->documentCache && $this->documentCache['input'] === $normalized) {
            return $this->documentCache['document'];
        }

        $lines = explode("\n", $normalized);
        $blocks = [];
        $headings = [];
        $paragraph = [];
        $list = null;
        $quote = [];
        $align = null;
        $alignLines = [];
        $preformatted = false;
        $preformattedLines = [];
        $code = null;
        $codeLines = [];
        $table = [];
        $usedHeadingAnchors = [];

        $flushParagraph = static function () use (&$paragraph, &$blocks): void {
            if ([] === $paragraph) {
                return;
            }

            $blocks[] = '<p>'.self::renderParagraphLines($paragraph).'</p>';
            $paragraph = [];
        };

        $flushList = static function () use (&$list, &$blocks): void {
            if (null === $list || [] === $list) {
                $list = null;

                return;
            }

            $blocks[] = implode('', array_map(
                static fn (\stdClass $listNode): string => self::renderListNode($listNode),
                $list,
            ));
            $list = null;
        };

        $flushQuote = static function () use (&$quote, &$blocks): void {
            if ([] === $quote) {
                return;
            }

            $content = array_map(
                static fn (string $line): string => '<p>'.self::renderInline($line).'</p>',
                $quote,
            );

            $blocks[] = '<blockquote>'.implode('', $content).'</blockquote>';
            $quote = [];
        };

        $flushAlign = static function () use (&$align, &$alignLines, &$blocks): void {
            if (null === $align) {
                return;
            }

            $safeAlign = in_array($align, ['left', 'center', 'right', 'justify'], true) ? $align : 'left';
            $inner = [];
            $buffer = [];

            $pushBuffer = static function () use (&$buffer, &$inner): void {
                if ([] === $buffer) {
                    return;
                }

                $inner[] = '<p>'.self::renderInline(implode(' ', $buffer)).'</p>';
                $buffer = [];
            };

            foreach ($alignLines as $line) {
                if ('' === trim($line)) {
                    $pushBuffer();

                    continue;
                }

                $buffer[] = trim($line);
            }

            $pushBuffer();

            if ([] !== $inner) {
                $blocks[] = sprintf('<div class="article-align-%s">%s</div>', $safeAlign, implode('', $inner));
            }

            $align = null;
            $alignLines = [];
        };

        $flushCode = static function () use (&$code, &$codeLines, &$blocks): void {
            if (null === $code) {
                return;
            }

            $blocks[] = self::renderCodeBlock($code, $codeLines);

            $code = null;
            $codeLines = [];
        };

        $flushPreformatted = static function () use (&$preformatted, &$preformattedLines, &$blocks): void {
            if (!$preformatted) {
                return;
            }

            $blocks[] = sprintf(
                '<pre class="article-preformatted">%s</pre>',
                htmlspecialchars(implode("\n", $preformattedLines), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );

            $preformatted = false;
            $preformattedLines = [];
        };

        $flushTable = static function () use (&$table, &$blocks): void {
            if ([] === $table) {
                return;
            }

            $header = array_shift($table);
            $headCells = array_map(
                static fn (string $cell): string => '<th>'.self::renderInline($cell).'</th>',
                $header,
            );

            $bodyRows = array_map(
                static fn (array $row): string => '<tr>'.implode('', array_map(
                    static fn (string $cell): string => '<td>'.self::renderInline($cell).'</td>',
                    $row,
                )).'</tr>',
                $table,
            );

            $blocks[] = sprintf(
                '<div class="article-table-wrap"><table><thead><tr>%s</tr></thead><tbody>%s</tbody></table></div>',
                implode('', $headCells),
                implode('', $bodyRows),
            );

            $table = [];
        };

        for ($index = 0, $lineCount = count($lines); $index < $lineCount; ++$index) {
            $line = $lines[$index];

            if (null !== $code) {
                if (null !== self::parseCodeFence($line)) {
                    $flushCode();
                } else {
                    $codeLines[] = $line;
                }

                continue;
            }

            if ($preformatted) {
                if (':::' === trim($line)) {
                    $flushPreformatted();
                } else {
                    $preformattedLines[] = $line;
                }

                continue;
            }

            if (null !== $align) {
                if (':::' === trim($line)) {
                    $flushAlign();
                } else {
                    $alignLines[] = $line;
                }

                continue;
            }

            $codeFenceLanguage = self::parseCodeFence($line);
            if (null !== $codeFenceLanguage) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $flushPreformatted();
                $code = $codeFenceLanguage;

                continue;
            }

            if (':::pre' === strtolower(trim($line))) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $preformatted = true;

                continue;
            }

            if (preg_match('/^:::(left|center|right|justify)\s*$/i', trim($line), $matches)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $flushPreformatted();
                $align = strtolower($matches[1]);

                continue;
            }

            if ('' === trim($line)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $flushPreformatted();

                continue;
            }

            if (self::isTableStart($lines, $index)) {
                $flushParagraph();
                $flushList();
                $flushQuote();

                $table[] = self::parseTableRow($line);
                ++$index;

                while ($index + 1 < $lineCount && self::isTableRow($lines[$index + 1])) {
                    ++$index;
                    $table[] = self::parseTableRow($lines[$index]);
                }

                continue;
            }

            if (preg_match('/^\s*([-*_])(?:\s*\1){2,}\s*$/', $line)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $blocks[] = '<hr>';

                continue;
            }

            $heading = self::parseHeading($line);
            if (null !== $heading) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $level = $heading['level'];
                $content = self::renderInline($heading['title']);

                if (7 === $level) {
                    $blocks[] = '<p class="article-heading-7">'.$content.'</p>';
                } else {
                    $anchor = self::createUniqueHeadingAnchor($heading['title'], $usedHeadingAnchors);
                    $headings[] = [
                        'id' => $anchor,
                        'level' => $level,
                        'title' => self::normalizeHeadingTitleForTableOfContents($heading['title']),
                    ];
                    $blocks[] = sprintf('<h%d id="%s">%s</h%d>', $level, $anchor, $content, $level);
                }

                continue;
            }

            if (preg_match('/^\s*>\s?(.*)$/', $line, $matches)) {
                $flushParagraph();
                $flushList();
                $flushTable();
                $quote[] = trim($matches[1]);

                continue;
            }

            if (null !== self::parseListLine($line)) {
                $flushParagraph();
                $flushQuote();
                $flushTable();
                $list = self::consumeListBlock($lines, $index);

                continue;
            }

            $flushList();
            $flushTable();
            $flushQuote();
            $paragraph[] = trim($line);
        }

        $flushTable();
        $flushList();
        $flushParagraph();
        $flushQuote();
        $flushAlign();
        $flushCode();
        $flushPreformatted();

        $document = [
            'html' => implode("\n", $blocks),
            'headings' => $headings,
        ];

        $this->documentCache = [
            'input' => $normalized,
            'document' => $document,
        ];

        return $document;
    }

    private static function renderParagraphLines(array $lines): string
    {
        $result = '';
        $previousEndedWithBreak = false;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            $lineBreak = str_ends_with($trimmed, '\\');
            $content = $lineBreak ? rtrim(substr($trimmed, 0, -1)) : $trimmed;

            if ($index > 0) {
                $result .= $previousEndedWithBreak ? self::LINE_BREAK_TOKEN : ' ';
            }

            $result .= $content;
            $previousEndedWithBreak = $lineBreak;
        }

        return self::renderInline($result);
    }

    private static function parseCodeFence(string $line): ?string
    {
        if (1 !== preg_match('/^ {0,3}```([a-z0-9_-]+)?\s*$/i', $line, $matches)) {
            return null;
        }

        return isset($matches[1]) ? strtolower($matches[1]) : '';
    }

    /**
     * @param list<string> $lines
     */
    private static function renderCodeBlock(?string $language, array $lines): string
    {
        $languageClass = '' !== (string) $language ? ' class="language-'.htmlspecialchars((string) $language, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';
        $renderedLines = [];

        foreach ($lines as $index => $line) {
            $renderedLines[] = sprintf(
                '<span class="article-code-line"><span class="article-code-line-number" aria-hidden="true">%d</span><span class="article-code-line-content">%s</span></span>',
                $index + 1,
                htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return sprintf(
            '<div class="article-code-block"><div class="article-code-scroll"><pre><code%s>%s</code></pre></div></div>',
            $languageClass,
            implode("\n", $renderedLines),
        );
    }

    private static function normalizeHeadingTitleForTableOfContents(string $title): string
    {
        $rendered = self::renderInline($title);
        $plainText = strip_tags($rendered);
        $decoded = html_entity_decode($plainText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $normalizedWhitespace = preg_replace('/\s+/u', ' ', $decoded) ?? $decoded;

        return trim($normalizedWhitespace);
    }

    /**
     * @param list<string> $lines
     * @return list<\stdClass>
     */
    private static function consumeListBlock(array $lines, int &$index): array
    {
        $rootLists = [];
        $stack = [];
        $lineCount = count($lines);

        for ($cursor = $index; $cursor < $lineCount; ++$cursor) {
            $item = self::parseListLine($lines[$cursor]);
            if (null === $item) {
                break;
            }

            self::pushListItem($rootLists, $stack, $item);
        }

        $index = $cursor - 1;

        return $rootLists;
    }

    /**
     * @return array{type: string, indent: int, content: string}|null
     */
    private static function parseListLine(string $line): ?array
    {
        if (preg_match('/^(?<indent>\s*)(?<marker>[-*])\s+(?<content>.+)$/', $line, $matches) === 1) {
            return [
                'type' => 'ul',
                'indent' => self::measureIndent($matches['indent']),
                'content' => trim($matches['content']),
            ];
        }

        if (preg_match('/^(?<indent>\s*)\d+\.\s+(?<content>.+)$/', $line, $matches) === 1) {
            return [
                'type' => 'ol',
                'indent' => self::measureIndent($matches['indent']),
                'content' => trim($matches['content']),
            ];
        }

        return null;
    }

    /**
     * @param list<\stdClass> $rootLists
     * @param list<\stdClass> $stack
     * @param array{type: string, indent: int, content: string} $item
     */
    private static function pushListItem(array &$rootLists, array &$stack, array $item): void
    {
        while ([] !== $stack && $item['indent'] < end($stack)->indent) {
            array_pop($stack);
        }

        $current = [] !== $stack ? end($stack) : null;

        if (null !== $current && $item['indent'] > $current->indent) {
            $parentItem = self::getLastListItem($current);
            if (null !== $parentItem) {
                $nestedList = self::createListNode($item['type'], $item['indent'], $parentItem);
                $parentItem->children[] = $nestedList;
                $stack[] = $nestedList;
                self::appendListItem($nestedList, $item['content']);

                return;
            }
        }

        if (null !== $current && $item['indent'] === $current->indent && $item['type'] === $current->type) {
            self::appendListItem($current, $item['content']);

            return;
        }

        if (null !== $current && $item['indent'] === $current->indent && $item['type'] !== $current->type) {
            array_pop($stack);
            $current = [] !== $stack ? end($stack) : null;
        }

        if (null !== $current && $item['indent'] > $current->indent) {
            $parentItem = self::getLastListItem($current);
            if (null !== $parentItem) {
                $nestedList = self::createListNode($item['type'], $item['indent'], $parentItem);
                $parentItem->children[] = $nestedList;
                $stack[] = $nestedList;
                self::appendListItem($nestedList, $item['content']);

                return;
            }
        }

        $parentItem = null;
        if (null !== $current) {
            $parentItem = $current->parentItem instanceof \stdClass ? $current->parentItem : null;
        }

        $newList = self::createListNode($item['type'], $item['indent'], $parentItem);
        if (null === $parentItem) {
            $rootLists[] = $newList;
        } else {
            $parentItem->children[] = $newList;
        }

        $stack[] = $newList;
        self::appendListItem($newList, $item['content']);
    }

    private static function createListNode(string $type, int $indent, ?\stdClass $parentItem): \stdClass
    {
        $node = new \stdClass();
        $node->type = $type;
        $node->indent = $indent;
        $node->parentItem = $parentItem;
        $node->items = [];

        return $node;
    }

    private static function appendListItem(\stdClass $listNode, string $content): void
    {
        $item = new \stdClass();
        $item->content = $content;
        $item->children = [];
        $listNode->items[] = $item;
    }

    private static function getLastListItem(\stdClass $listNode): ?\stdClass
    {
        if ([] === $listNode->items) {
            return null;
        }

        return $listNode->items[array_key_last($listNode->items)];
    }

    private static function renderListNode(\stdClass $listNode): string
    {
        $items = array_map(static function (\stdClass $item): string {
            $children = array_map(
                static fn (\stdClass $childList): string => self::renderListNode($childList),
                $item->children,
            );

            return '<li>'.self::renderInline($item->content).implode('', $children).'</li>';
        }, $listNode->items);

        return sprintf('<%1$s>%2$s</%1$s>', $listNode->type, implode('', $items));
    }

    private static function measureIndent(string $indent): int
    {
        return strlen(str_replace("\t", '    ', $indent));
    }

    /**
     * @return array{level: int, title: string}|null
     */
    private static function parseHeading(string $line): ?array
    {
        if (preg_match('/^(#{1,7})\s+(.*)$/', ltrim($line), $matches) !== 1) {
            return null;
        }

        $title = trim($matches[2]);
        if ('' === $title) {
            return null;
        }

        return [
            'level' => strlen($matches[1]),
            'title' => $title,
        ];
    }

    /**
     * @param array<string, int> $usedAnchors
     */
    private static function createUniqueHeadingAnchor(string $title, array &$usedAnchors): string
    {
        $base = self::slugifyHeading($title);
        $count = $usedAnchors[$base] ?? 0;
        $usedAnchors[$base] = $count + 1;

        return 0 === $count ? $base : sprintf('%s-%d', $base, $count + 1);
    }

    private static function slugifyHeading(string $title): string
    {
        $normalized = trim($title);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $ascii = is_string($transliterated) ? $transliterated : $normalized;
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        $ascii = trim($ascii, '-');

        return '' !== $ascii ? $ascii : 'section';
    }

    private static function renderInline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace_callback(
            '/!\[([^\]]*)\]\((((?i:https?):\/\/|\/uploads\/)[^\s)]+)\)/',
            static function (array $matches): string {
                $source = htmlspecialchars_decode($matches[2], ENT_QUOTES);
                if (!self::isAllowedImageSource($source)) {
                    return $matches[0];
                }

                return sprintf(
                    '<img src="%s" alt="%s" loading="lazy">',
                    htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars(htmlspecialchars_decode($matches[1], ENT_QUOTES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );
            },
            $escaped,
        ) ?? $escaped;

        $escaped = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i',
            static fn (array $matches): string => sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                htmlspecialchars(htmlspecialchars_decode($matches[2], ENT_QUOTES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $matches[1],
            ),
            $escaped,
        ) ?? $escaped;

        $patterns = [
            '/\+\+(.+?)\+\+/s' => '<u>$1</u>',
            '/\*\*(.+?)\*\*/s' => '<strong>$1</strong>',
            '/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s' => '<em>$1</em>',
            '/`([^`\n]+)`/' => '<code>$1</code>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $escaped = preg_replace($pattern, $replacement, $escaped) ?? $escaped;
        }

        return str_replace(self::LINE_BREAK_TOKEN, '<br>', $escaped);
    }

    private static function isAllowedImageSource(string $source): bool
    {
        if (preg_match('/^https?:\/\//i', $source) === 1) {
            return true;
        }

        if (!str_starts_with($source, '/uploads/')) {
            return false;
        }

        $path = parse_url($source, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/uploads/')) {
            return false;
        }

        $decodedPath = str_replace('\\', '/', rawurldecode($path));
        $segments = explode('/', trim($decodedPath, '/'));

        foreach ($segments as $segment) {
            if ('.' === $segment || '..' === $segment) {
                return false;
            }
        }

        return true;
    }

    private static function isTableStart(array $lines, int $index): bool
    {
        if (!isset($lines[$index + 1])) {
            return false;
        }

        return self::isTableRow($lines[$index]) && self::isTableSeparator($lines[$index + 1]);
    }

    private static function isTableRow(string $line): bool
    {
        return str_contains($line, '|') && preg_match('/^\s*\|?.+\|.+\|?\s*$/', $line) === 1;
    }

    private static function isTableSeparator(string $line): bool
    {
        $cells = self::splitTableCells($line);

        if ([] === $cells) {
            return false;
        }

        foreach ($cells as $cell) {
            if (preg_match('/^:?-{3,}:?$/', $cell) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function parseTableRow(string $line): array
    {
        return self::splitTableCells($line);
    }

    /**
     * @return list<string>
     */
    private static function splitTableCells(string $line): array
    {
        $trimmed = trim($line);
        $trimmed = trim($trimmed, '|');

        if ('' === $trimmed) {
            return [];
        }

        return array_map(
            static fn (string $cell): string => trim($cell),
            explode('|', $trimmed),
        );
    }
}
