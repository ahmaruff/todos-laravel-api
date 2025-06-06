<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Services\TodoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

class TodoExportController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    #[OA\Get(
        path: "/api/todos/export",
        summary: "Export todos to Excel",
        description: "Export filtered todos to Excel file and return download URL",
        tags: ["Todo Export"],
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Excel file exported successfully",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                 new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(
                                            property: "total_row",
                                            type: "integer",
                                            description: "Number of todos exported",
                                            example: 25
                                        ),
                                         new OA\Property(
                                            property: "total_time_tracked",
                                            type: "integer",
                                            description: "Sum of todos time tracked if status = completed",
                                            example: 25
                                         ),
                                        new OA\Property(
                                            property: "filename",
                                            type: "string",
                                            description: "Generated Excel filename",
                                            example: "todos_export_2025_06_06_123456.xlsx"
                                        ),
                                        new OA\Property(
                                            property: "url",
                                            type: "string",
                                            description: "Download URL for the exported file",
                                            example: "http://localhost:8000/api/todos/download/todos_export_2025_06_06_123456.xlsx"
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
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error during export",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function excel(Request $request)
    {
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

        try {
            $result = $this->todoService->export($filters);
            $filename = $result['filename'];

            $downloadUrl = route('todos.download', $filename);
            $result['url'] = $downloadUrl;
            return ResponseJsonCommand::responseSuccess('success export', $result);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    #[OA\Get(
        path: "/api/todos/download/{filename}",
        summary: "Download exported Excel file",
        description: "Download the exported Excel file by filename",
        tags: ["Todo Export"],
        parameters: [
            new OA\Parameter(
                name: "filename",
                in: "path",
                description: "The filename of the exported Excel file",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                    pattern: "^[a-zA-Z0-9_.-]+\.xlsx$",
                    example: "todos_export_2025_06_06_123456.xlsx"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "File downloaded successfully",
                content: new OA\MediaType(
                    mediaType: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    schema: new OA\Schema(
                        type: "string",
                        format: "binary",
                        description: "Excel file content"
                    )
                ),
                headers: [
                    new OA\Header(
                        header: "Content-Dispotition",
                        description: "Attachment with filename",
                        schema: new OA\Schema(type: "string", example: "attachment; filename=todos_export.xlsx")
                    ),
                    new OA\Header(
                        header: "Content-Type",
                        description: "MIME type of the file",
                        schema: new OA\Schema(type: "string", example: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
                    )
                ]
            ),
            new OA\Response(
                response: 404,
                description: "File not found",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/BaseResponse"),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    properties: [
                                        new OA\Property(property: "filename", type: "string"),
                                        new OA\Property(property: "exists", type: "boolean", example: false)
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid filename format",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error during download",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function download(string $filename)
    {
        try {
            $path = 'exports/' . basename($filename);

            if (!Storage::disk('local')->exists($path)) {
                return ResponseJsonCommand::responseFail('File not found', [
                    'filename' => $filename,
                    'exists' => false
                ], Response::HTTP_NOT_FOUND);
            }

            return Storage::disk('local')->download($path);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
