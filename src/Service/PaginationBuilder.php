<?php

declare(strict_types=1);

namespace App\Service;

final class PaginationBuilder
{
    /**
     * @return list<int|string>
     */
    public function buildPaginationItems(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 7) {
            return range(1, max(1, $totalPages));
        }

        $pages = [1, $totalPages];

        for ($page = $currentPage - 1; $page <= $currentPage + 1; ++$page) {
            if ($page > 1 && $page < $totalPages) {
                $pages[] = $page;
            }
        }

        sort($pages);

        $items = [];
        $previousPage = null;

        foreach (array_values(array_unique($pages)) as $page) {
            if (null !== $previousPage && $page - $previousPage > 1) {
                $items[] = '...';
            }

            $items[] = $page;
            $previousPage = $page;
        }

        return $items;
    }
}
