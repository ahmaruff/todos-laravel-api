<?php

namespace App\OpenApi\Parameters;

use OpenApi\Attributes as OA;

class TodoFilterParameter
{

    public static function getFilterParameters(): array
    {
        return [
            new OA\Parameter(
                name: "title",
                in: "query",
                description: "Filter by title (partial match)",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "assignee",
                in: "query",
                description: "Filter by assignee",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filter by status",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["pending", "open", "in_progress", "completed"]
                )
            ),
            new OA\Parameter(
                name: "priority",
                in: "query",
                description: "Filter by priority",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["low", "medium", "high"]
                )
            ),
            new OA\Parameter(
                name: "start",
                in: "query",
                description: "Filter by due date start range",
                required: false,
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "end",
                in: "query",
                description: "Filter by due date end range",
                required: false,
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "min",
                in: "query",
                description: "Filter by minimum time tracked (minutes)",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 0)
            ),
            new OA\Parameter(
                name: "max",
                in: "query",
                description: "Filter by maximum time tracked (minutes)",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 0)
            )
        ];
    }

    public static function getPaginationParameters(): array
    {
        return [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number for pagination",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: "paginate",
                in: "query",
                description: "Enable pagination",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["true", "false", "1", "0"],
                    default: "false"
                )
            )
        ];
    }

    public static function getAllParameters(): array
    {
        return array_merge(
            self::getFilterParameters(),
            self::getPaginationParameters()
        );
    }
}
