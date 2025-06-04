<?php
namespace App\Commands;

use Illuminate\Pagination\LengthAwarePaginator;

class ModelPaginationCommand
{
    public static function execute(LengthAwarePaginator $paginator, array $filters = [], ?string $baseUrl = null): array
    {
        $baseUrl = $baseUrl ?? url()->current();

        $queryParams = collect($filters)->filter()->all();

        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $prevPageUrl = $currentPage > 1
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage - 1]))
            : null;

        $nextPageUrl = $currentPage < $lastPage
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage + 1]))
            : null;

        return [
            'current_page' => $currentPage,
            'total_page' => $lastPage,
            'per_page' => $paginator->perPage(),
            'last_page' => $lastPage,
            'previous_page_url' => $prevPageUrl,
            'next_page_url' => $nextPageUrl,
            'total' => $paginator->total(),
        ];
    }
}
