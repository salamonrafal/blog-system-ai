<?php

declare(strict_types=1);

namespace App\Service;

final class ArticleMarkupRenderer
{
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
        $code = null;
        $codeLines = [];

        $flushParagraph = static function () use (&$paragraph, &$blocks): void {
            if ([] === $paragraph) {
                return;
            }

            $blocks[] = '<p>'.self::renderInline(implode(' ', $paragraph)).'</p>';
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

        foreach ($lines as $line) {
            if (null !== $code) {
                if (preg_match('/^```/', $line)) {
                    $flushCode();
                } else {
                    $codeLines[] = $line;
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
                $code = isset($matches[1]) ? strtolower($matches[1]) : '';

                continue;
            }

            if (preg_match('/^:::(left|center|right|justify)\s*$/i', trim($line), $matches)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $align = strtolower($matches[1]);

                continue;
            }

            if ('' === trim($line)) {
                $flushParagraph();
                $flushList();
                $flushQuote();

                continue;
            }

            if (preg_match('/^(#{1,7})\s+(.*)$/', ltrim($line), $matches)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
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

        return implode("\n", $blocks);
    }

    private static function renderInline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace_callback(
            '/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/i',
            static fn (array $matches): string => sprintf(
                '<img src="%s" alt="%s" loading="lazy">',
                htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $escaped,
        ) ?? $escaped;

        $escaped = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i',
            static fn (array $matches): string => sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                htmlspecialchars($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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

        return $escaped;
    }
}
