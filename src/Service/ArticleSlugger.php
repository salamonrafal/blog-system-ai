<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class ArticleSlugger
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function slugify(string $value): string
    {
        return strtolower($this->slugger->slug($value)->toString());
    }
}
