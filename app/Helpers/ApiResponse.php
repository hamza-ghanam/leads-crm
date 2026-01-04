<?php

namespace App\Helpers;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        int   $status = Response::HTTP_OK
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    public static function error(
        ApiErrorCode $code,
        ?string      $message = null,
        int          $status = Response::HTTP_BAD_REQUEST,
        mixed        $data = null,
    ): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code->value,
            'message' => $message ?? $code->message(),
            'data'    => $data,
        ], $status);
    }
}
