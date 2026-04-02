<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArticleKeyword;
use App\Repository\ArticleKeywordRepository;

class ArticleKeywordNameGenerator
{
    public function __construct(
        private readonly ArticleKeywordRepository $articleKeywordRepository,
        private readonly SlugBasedUniqueNameGenerator $slugBasedUniqueNameGenerator,
    ) {
    }

    public function refreshName(ArticleKeyword $keyword): void
    {
        $keyword->setName($this->slugBasedUniqueNameGenerator->generate(
            $keyword->getName(),
            fn (string $candidate): bool => $this->articleKeywordRepository->nameExistsForLanguage(
                $candidate,
                $keyword->getLanguage()->value,
                $keyword->getId(),
            ),
            'keyword',
        ));
    }
}
