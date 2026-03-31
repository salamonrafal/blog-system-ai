<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\TopMenuItem;
use App\Repository\TopMenuItemRepository;
use App\Service\ArticleSlugger;
use App\Service\TopMenuItemUniqueNameGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TopMenuItemUniqueNameGeneratorTest extends TestCase
{
    public function testRefreshUniqueNameBuildsUniqueNameFromPolishLabel(): void
    {
        $menuItem = (new TopMenuItem())
            ->setLabel('pl', 'Kontakt')
            ->setLabel('en', 'Contact');

        $generator = new TopMenuItemUniqueNameGenerator(
            $this->createRepositoryMock(['kontakt']),
            new ArticleSlugger(),
        );

        $generator->refreshUniqueName($menuItem);

        $this->assertSame('kontakt-2', $menuItem->getUniqueName());
    }

    public function testRefreshUniqueNameFallsBackToAnyLabel(): void
    {
        $menuItem = (new TopMenuItem())
            ->setLabel('en', 'About me');

        $generator = new TopMenuItemUniqueNameGenerator(
            $this->createRepositoryMock([]),
            new ArticleSlugger(),
        );

        $generator->refreshUniqueName($menuItem);

        $this->assertSame('about-me', $menuItem->getUniqueName());
    }

    public function testRefreshUniqueNameKeepsFinalValueWithinColumnLimitWhenSuffixIsNeeded(): void
    {
        $baseLabel = str_repeat('a', 255);
        $menuItem = (new TopMenuItem())
            ->setLabel('pl', $baseLabel);

        $generator = new TopMenuItemUniqueNameGenerator(
            $this->createRepositoryMock([str_repeat('a', 255)]),
            new ArticleSlugger(),
        );

        $generator->refreshUniqueName($menuItem);

        $this->assertSame(255, strlen($menuItem->getUniqueName()));
        $this->assertStringEndsWith('-2', $menuItem->getUniqueName());
    }

    /**
     * @param list<string> $existingUniqueNames
     */
    private function createRepositoryMock(array $existingUniqueNames): TopMenuItemRepository
    {
        /** @var TopMenuItemRepository&MockObject $repository */
        $repository = $this->createMock(TopMenuItemRepository::class);
        $repository
            ->method('uniqueNameExists')
            ->willReturnCallback(
                static fn (string $uniqueName, ?int $ignoreId = null): bool => in_array($uniqueName, $existingUniqueNames, true)
            );

        return $repository;
    }
}
