<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateTodoRequest",
    description: "Request payload for updating a todo (all fields optional)",
    type: "object"
)]
class UpdateTodoRequestSchema
{
    #[OA\Property(
        property: "title",
        type: "string",
        description: "Title of the todo item"
    )]
    public $title;

    #[OA\Property(
        property: "assignee",
        type: "string",
        description: "Person assigned to this todo",
        nullable: true
    )]
    public $assignee;

    #[OA\Property(
        property: "due_date",
        type: "string",
        format: "date",
        description: "Due date for the todo (must be today or future date)"
    )]
    public $due_date;

    #[OA\Property(
        property: "time_tracked",
        type: "integer",
        description: "Time tracked in minutes",
        nullable: true,
        minimum: 0
    )]
    public $time_tracked;

    #[OA\Property(
        property: "status",
        type: "string",
        description: "Status of the todo",
        enum: ["pending", "open", "in_progress", "completed"]
    )]
    public $status;

    #[OA\Property(
        property: "priority",
        type: "string",
        description: "Priority level of the todo",
        enum: ["low", "medium", "high"]
    )]
    public $priority;
}
