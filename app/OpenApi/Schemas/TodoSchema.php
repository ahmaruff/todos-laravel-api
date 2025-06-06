<?php
namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: "Todo",
    description: "Todo",
    type: "object"
)]
class TodoSchema
{
     #[OA\Property(
        property: "id",
        type: "integer",
        description: "Unique identifier for the todo item",
        example: 1
    )]
    public $id;

    #[OA\Property(
        property: "title",
        type: "string",
        description: "Title of the todo item",
        maxLength: 255,
        example: "Complete project documentation"
    )]
    public $title;

    #[OA\Property(
        property: "assignee",
        type: "string",
        description: "Person assigned to this todo",
        nullable: true,
        example: "John Doe"
    )]
    public $assignee;

    #[OA\Property(
        property: "due_date",
        type: "string",
        format: "date-time",
        description: "Due date and time for the todo",
        nullable: true,
        example: "2025-06-15T14:30:00Z"
    )]
    public $due_date;

    #[OA\Property(
        property: "time_tracked",
        type: "integer",
        description: "Time tracked in minutes",
        nullable: true,
        example: 120
    )]
    public $time_tracked;

    #[OA\Property(
        property: "status",
        type: "string",
        description: "Current status of the todo",
        enum: ["pending", "open", "in_progress", "completed"],
        example: "in_progress"
    )]
    public $status;

    #[OA\Property(
        property: "priority",
        type: "string",
        description: "Priority level of the todo",
        enum: ["low", "medium", "high"],
        example: "high"
    )]
    public $priority;

    #[OA\Property(
        property: "created_at",
        type: "string",
        format: "date-time",
        description: "When the todo was created",
        example: "2025-06-06T10:00:00Z"
    )]
    public $created_at;

    #[OA\Property(
        property: "updated_at",
        type: "string",
        format: "date-time",
        description: "When the todo was last updated",
        example: "2025-06-06T15:30:00Z"
    )]
    public $updated_at;
}
