<?php

namespace App\OpenApi\Schemas;
use App\Commands\ResponseJsonCommand;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ErrorResponse",
    description: "Error response",
    type: "object"
)]
class ErrorResponseSchema
{
    #[OA\Property(
        property: "status",
        type: "string",
        enum: [ResponseJsonCommand::SUCCESS, ResponseJsonCommand::FAIL, ResponseJsonCommand::ERROR],
        example: ResponseJsonCommand::SUCCESS,
    )]
    public $status;

    #[OA\Property(
        property: "code",
        type: "integer",
        example: 200
    )]
    public $code;


    #[OA\Property(
        property: "message",
        type: "string",
        example: "Whoops! Something wrong"
    )]
    public $message;

    #[OA\Property(
        property: "data",
        type: "object",
        properties: [
            new OA\Property(
                property: "error",
                type: "string",
                example: "Whoops! Something wrong"
            )
        ]
    )]
    public $data;
}
