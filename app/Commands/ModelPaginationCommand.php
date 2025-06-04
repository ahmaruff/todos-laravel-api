<?php
namespace App\Commands;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ModelPaginationCommand
{
    public static function execute(Builder $query, int $perPage = 10, int $page = 1, array $filters = [], ?string $baseUrl = null): array
    {
        $baseUrl = $baseUrl ?? url()->current();
        $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        $queryParams = array_filter($filters);

        $prevPageUrl = $paginator->currentPage() > 1
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1]))
            : null;

        $nextPageUrl = $paginator->currentPage() < $paginator->lastPage()
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1]))
            : null;

        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total_pages' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'previous_page_url' => $prevPageUrl,
                'next_page_url' => $nextPageUrl,
                'total' => $paginator->total(),
            ]
        ];
    }
}
