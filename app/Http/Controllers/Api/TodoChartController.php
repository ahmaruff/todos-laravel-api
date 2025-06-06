<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Services\TodoService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TodoChartController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    #[OA\Get(
        path: "/api/todos/chart",
        summary: "Get todos's chart with optional filtering",
        description: "Retrieve todos's chart with optional filtering",
        tags: ["Chart"],
        parameters: [
            new OA\Parameter(
                name: "type",
                in: "query",
                description: "Filter by chart type",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["status", "priority", "assignee"]
                )
            ),
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
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Todos retrieved successfully. Response format depends on 'type' parameter.",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    description: "When type filter is exists: contain only chart by defined by type",
                                    properties: [
                                        new OA\Property(
                                            property: "status_summary",
                                            type: "object",
                                            properties: [
                                                new OA\Property(
                                                    property: "pending",
                                                    type: "integer"
                                                ),
                                                new OA\Property(
                                                    property: "open",
                                                    type: "integer"
                                                ),
                                                new OA\Property(
                                                    property: "in_progress",
                                                    type: "integer"
                                                ),
                                                new OA\Property(
                                                    property: "completed",
                                                    type: "integer"
                                                ),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: "priority_summary",
                                            type: "object",
                                            properties: [
                                                new OA\Property(
                                                    property: "low",
                                                    type: "integer"
                                                ),
                                                new OA\Property(
                                                    property: "medium",
                                                    type: "integer"
                                                ),
                                                new OA\Property(
                                                    property: "high",
                                                    type: "integer"
                                                )
                                            ]
                                        ),
                                        new OA\Property(
                                            property: "assignee_summary",
                                            type: "object",
                                            properties: [
                                                new OA\Property(
                                                    property: "user",
                                                    type: "object",
                                                    properties: [
                                                        new OA\Property(
                                                            property: "total_todos",
                                                            type: "integer"
                                                        ),
                                                        new OA\Property(
                                                            property: "total_pending_todos",
                                                            type: "integer"
                                                        ),
                                                        new OA\Property(
                                                            property: "total_timetracked_completed_todos",
                                                            type: "integer"
                                                        )
                                                    ]
                                                ),
                                            ]
                                        ),
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad request - invalid parameters",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function index(Request $request)
    {
        $filters = [];

        $start = $request->query('start', null);
        $end = $request->query('end', null);
        $min = $request->query('min', null);
        $max = $request->query('max', null);
        $title = $request->query('title', null);
        $assignee = $request->query('assignee', null);
        $status = $request->query('status', null);
        $priority = $request->query('priority', null);

        $filters = [
            'start' => $start,
            'end' => $end,
            'min' => $min,
            'max' => $max,
            'title' => $title,
            'assignee' => $assignee,
            'status' => $status,
            'priority' => $priority
        ];

        $type = $request->query('type');
        $type = strtolower(trim($type));

        try {
            $data  = [];
            switch ($type) {
                case 'status':
                    $data['status_summary'] = $this->todoService->statusChart($filters);
                    break;
                case 'priority':
                    $data['priority_summary'] = $this->todoService->priorityChart($filters);
                    break;
                case 'assignee':
                    $data['assignee_summary'] = $this->todoService->assigneeChart($filters);
                    break;
                default:
                    $data['status_summary'] = $this->todoService->statusChart($filters);
                    $data['priority_summary'] = $this->todoService->priorityChart($filters);
                    $data['assignee_summary'] = $this->todoService->assigneeChart($filters);
                    break;
            }

            return ResponseJsonCommand::responseSuccess("success get chart", $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
