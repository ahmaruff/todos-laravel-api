<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Pagination",
    description: "Pagination information",
    type: "object"
)]
class PaginationSchema
{
    #[OA\Property(property: "current_page", type: "integer", example: 1)]
    public $current_page;

    #[OA\Property(property: "last_page", type: "integer", example: 5)]
    public $last_page;

    #[OA\Property(property: "total_pages", type: "integer", example: 5)]
    public $total_pages;

    #[OA\Property(property: "per_page", type: "integer", example: 10)]
    public $per_page;

    #[OA\Property(property: "previous_page_url", type: "string", nullable: true, example: null)]
    public $previous_page_url;

    #[OA\Property(property: "next_page_url", type: "string", nullable: true, example: "http://example.com/api/todos?page=2")]
    public $next_page_url;

    #[OA\Property(property: "total", type: "integer", example: 47)]
    public $total;
}
