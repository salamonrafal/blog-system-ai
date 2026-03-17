<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\ArticleMarkupRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ArticleMarkupExtension extends AbstractExtension
{
    public function __construct(private readonly ArticleMarkupRenderer $renderer)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('article_markup', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    public function render(string $content): string
    {
        return $this->renderer->render($content);
    }
}
