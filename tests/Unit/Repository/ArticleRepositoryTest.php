<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Repository\ArticleRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ArticleRepositoryTest extends TestCase
{
    public function testFindRecentForTopMenuSelectionReturnsEmptyArrayForNonPositiveLimit(): void
    {
        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);

        /** @var ArticleRepository&MockObject $repository */
        $repository = $this->getMockBuilder(ArticleRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([])
            ->getMock();

        $this->assertSame([], $repository->findRecentForTopMenuSelection(0));
        $this->assertSame([], $repository->findRecentForTopMenuSelection(-5));
    }
}
