<?php
namespace App\OpenApi\Schemas;

use App\Commands\ResponseJsonCommand;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BaseResponse",
    description: "API base response structure",
    type: "object"
)]
class BaseResponseSchema
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
        description: "Response data - structure varies by endpoint"
    )]
    public $data;
}
