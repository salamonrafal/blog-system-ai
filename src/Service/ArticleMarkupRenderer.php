<?php

declare(strict_types=1);

namespace App\Service;

final class ArticleMarkupRenderer
{
    private const LINE_BREAK_TOKEN = '@@ARTICLE_LINE_BREAK@@';

    public function render(string $input): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($input));

        if ('' === $normalized) {
            return '';
        }

        $lines = explode("\n", $normalized);
        $blocks = [];
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

        $flushParagraph = static function () use (&$paragraph, &$blocks): void {
            if ([] === $paragraph) {
                return;
            }

            $blocks[] = '<p>'.self::renderParagraphLines($paragraph).'</p>';
            $paragraph = [];
        };

        $flushList = static function () use (&$list, &$blocks): void {
            if (null === $list || [] === $list['items']) {
                $list = null;

                return;
            }

            $tag = $list['type'];
            $items = array_map(
                static fn (string $item): string => '<li>'.self::renderInline($item).'</li>',
                $list['items'],
            );

            $blocks[] = sprintf('<%1$s>%2$s</%1$s>', $tag, implode('', $items));
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

            $languageClass = '' !== $code ? ' class="language-'.htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';
            $blocks[] = sprintf(
                '<pre><code%s>%s</code></pre>',
                $languageClass,
                htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );

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
                if (preg_match('/^```/', $line)) {
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

            if (preg_match('/^```([a-z0-9_-]+)?\s*$/i', trim($line), $matches)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $flushPreformatted();
                $code = isset($matches[1]) ? strtolower($matches[1]) : '';

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

            if (preg_match('/^(#{1,7})\s+(.*)$/', ltrim($line), $matches)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $flushTable();
                $level = strlen($matches[1]);
                $content = self::renderInline(trim($matches[2]));

                if (7 === $level) {
                    $blocks[] = '<p class="article-heading-7">'.$content.'</p>';
                } else {
                    $blocks[] = sprintf('<h%d>%s</h%d>', $level, $content, $level);
                }

                continue;
            }

            if (preg_match('/^\s*>\s?(.*)$/', $line, $matches)) {
                $flushParagraph();
                $flushList();
                $quote[] = trim($matches[1]);

                continue;
            }

            if (preg_match('/^\s*([-*])\s+(.*)$/', $line, $matches)) {
                $flushParagraph();
                $flushQuote();

                if (null === $list || 'ul' !== $list['type']) {
                    $flushList();
                    $list = ['type' => 'ul', 'items' => []];
                }

                $list['items'][] = trim($matches[2]);

                continue;
            }

            if (preg_match('/^\s*\d+\.\s+(.*)$/', $line, $matches)) {
                $flushParagraph();
                $flushQuote();

                if (null === $list || 'ol' !== $list['type']) {
                    $flushList();
                    $list = ['type' => 'ol', 'items' => []];
                }

                $list['items'][] = trim($matches[1]);

                continue;
            }

            $flushList();
            $flushQuote();
            $paragraph[] = trim($line);
        }

        $flushParagraph();
        $flushList();
        $flushQuote();
        $flushAlign();
        $flushCode();
        $flushPreformatted();
        $flushTable();

        return implode("\n", $blocks);
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

    private static function renderInline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace_callback(
            '/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/i',
            static fn (array $matches): string => sprintf(
                '<img src="%s" alt="%s" loading="lazy">',
                htmlspecialchars(htmlspecialchars_decode($matches[2], ENT_QUOTES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars(htmlspecialchars_decode($matches[1], ENT_QUOTES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
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
