<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PaginationBuilder;
use PHPUnit\Framework\TestCase;

final class PaginationBuilderTest extends TestCase
{
    public function testBuildPaginationItemsReturnsFullRangeForShortPagination(): void
    {
        $builder = new PaginationBuilder();

        $this->assertSame([1, 2, 3], $builder->buildPaginationItems(2, 3));
    }

    public function testBuildPaginationItemsReturnsCondensedRangeForLongPagination(): void
    {
        $builder = new PaginationBuilder();

        $this->assertSame([1, '...', 4, 5, 6, '...', 10], $builder->buildPaginationItems(5, 10));
    }

    public function testBuildPaginationItemsClampsEmptyPaginationToSinglePage(): void
    {
        $builder = new PaginationBuilder();

        $this->assertSame([1], $builder->buildPaginationItems(1, 0));
    }
}
