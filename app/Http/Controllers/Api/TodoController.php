<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\OpenApi\Parameters\TodoFilterParameter;
use App\Services\TodoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;


#[OA\Info(
    version: "1.0.0",
    title: "Todo CRUD API",
    description: "Todo list CRUD Web API"
)]
class TodoController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    #[OA\Get(
        path: "/api/todos",
        summary: "Get all todos with optional filtering and pagination",
        description: "Retrieve todos with optional filters. When paginate=true, response includes pagination metadata.",
        tags: ["Todos"],
        parameters: [
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
            ),
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
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Todos retrieved successfully. Response format depends on 'paginate' parameter.",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    description: "When paginate=false: contains only 'todos' array. When paginate=true: contains 'todos' array and 'pagination' object.",
                                    properties: [
                                        new OA\Property(
                                            property: "todos",
                                            type: "array",
                                            items: new OA\Items(ref: "#/components/schemas/Todo")
                                        ),
                                        new OA\Property(
                                            property: "pagination",
                                            ref: "#/components/schemas/Pagination",
                                            description: "Only present when paginate=true"
                                        )
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
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        $paginate = $request->query('paginate', 'false');

        $start = $request->query('start', null);
        $end = $request->query('end', null);
        $min = $request->query('min', null);
        $max = $request->query('max', null);
        $title = $request->query('title', null);
        $assignee = $request->query('assignee', null);
        $status = $request->query('status', null);
        $priority = $request->query('priority', null);

        if($paginate == 'true' || $paginate == 1 || $paginate == '1') {
            $paginate = true;
        } else {
            $paginate = false;
        }

        $filters = [
            'start' => $start ?? null,
            'end' => $end ?? null,
            'min' => $min ?? null,
            'max' => $max ?? null,
            'title' => $title ?? null,
            'assignee' => $assignee ?? null,
            'status' => $status ?? null,
            'priority' => $priority ?? null,
            'page' => $page ?? null,
            'per_page' => $perPage ?? null,
        ];

        try {
            $result = $this->todoService->list($filters, $paginate);

            if($paginate) {
                return ResponseJsonCommand::responseSuccess('success', $result);
            }

            return ResponseJsonCommand::responseSuccess('success', ['todos' => $result['todos'] ?? []]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    #[OA\Post(
        path: "/api/todos",
        summary: "Create new todo",
        description: "Create a new todo item with validation",
        tags: ["Todos"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Todo data",
            content: new OA\JsonContent(ref: "#/components/schemas/CreateTodoRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Todo created successfully",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "todo",
                                            ref: "#/components/schemas/Todo"
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "error",
                                            type: "object",
                                            additionalProperties: new OA\AdditionalProperties(
                                                type: "array",
                                                items: new OA\Items(type: "string")
                                            )
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function store(Request $request)
    {
        $rules = [
            'title' => ['required', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'time_tracked' => ['sometimes','nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$statusList)],
            'priority' => ['required', 'string',Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($request->json()->all(), $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        try {
            $todo = $this->todoService->save($validated);

            return ResponseJsonCommand::responseSuccess("success save todo", ['todo' => $todo], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    #[OA\Get(
        path: "/api/todos/{id}",
        summary: "Get single todo",
        description: "get single todo item by id",
        tags: ["Todos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Todo ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Todo retrieved successfully",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "todo",
                                            ref: "#/components/schemas/Todo"
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Not Found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function show(string $id)
    {
        try {
            $todo = $this->todoService->find($id);
            return ResponseJsonCommand::responseSuccess("success get todo", ['todo' => $todo]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


     #[OA\Put(
        path: "/api/todos/{id}",
        summary: "Update todo",
        description: "Update todo item with validation",
        tags: ["Todos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Todo ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Todo data",
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateTodoRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Todo updated successfully",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "todo",
                                            ref: "#/components/schemas/Todo"
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation failed",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "error",
                                            type: "object",
                                            additionalProperties: new OA\AdditionalProperties(
                                                type: "array",
                                                items: new OA\Items(type: "string")
                                            )
                                        )
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $rules = [
            'title' => ['sometimes','nullable', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'time_tracked' => ['sometimes','nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$statusList)],
            'priority' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($request->json()->all(), $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['id'] = $id;

        try {
            $todo = $this->todoService->save($validated);

            return ResponseJsonCommand::responseSuccess("success save todo", ['todo' => $todo], Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    #[OA\Delete(
        path: "/api/todos/{id}",
        summary: "Delete single todo",
        description: "Delete single todo item by id",
        tags: ["Todos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Todo ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Todo deleted successfully",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Not Found",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function destroy(string $id)
    {
        try {
            $result = $this->todoService->delete($id);
            return ResponseJsonCommand::responseSuccess("success delete todo");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
