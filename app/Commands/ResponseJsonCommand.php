<?php

namespace App\Commands;

use Symfony\Component\HttpFoundation\Response;

class ResponseJsonCommand
{
    public const SUCCESS = 'success';
    public const FAIL = 'fail';
    public const ERROR = 'error';

    public static function responseSuccess(string $message = 'success', $data = null, int $code = Response::HTTP_OK)
    {
        return self::render(self::SUCCESS, $code, $message, $data);
    }

    public static function responseFail(string $message = 'fail', $data = null, int $code = Response::HTTP_BAD_REQUEST)
    {
        return self::render(self::FAIL, $code, $message, $data);
    }

    public static function responseError( string $message = 'error', $data = null, int $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        return self::render(self::ERROR, $code, $message, $data);

    }

    public static function render(string $status, int $code, string $message, $data = null)
    {
        $res = [
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($res,$code);
    }
}
