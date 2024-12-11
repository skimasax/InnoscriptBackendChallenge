<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public string $message;
    public $data;

    const UNAUTHORIZED_STATUS_CODE = 401;
    const SUCCESS_STATUS_CODE = 200;
    const SUCCESS_WITH_DATA_STATUS_CODE = 201;
    const ERROR_STATUS_CODE = 400;
    const FAILED_VALIDATION_STATUS_CODE = 422;

    public function __construct($data, string $message = '')
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function successResponse(int $statusCode = self::SUCCESS_STATUS_CODE): JsonResponse
    {
        return response()->json([
            'status' => $statusCode,
            'success' => true,
            'message' => $this->message ?: 'Success processing request',
            'data' => $this->data,
        ], $statusCode);
    }

    public function errorResponse(int $statusCode = self::ERROR_STATUS_CODE): JsonResponse
    {
        return response()->json([
            'status' => $statusCode,
            'success' => false,
            'message' => $this->message ?: 'Error processing request',
        ], $statusCode);
    }

    public function validationErrorResponse(int $statusCode = self::FAILED_VALIDATION_STATUS_CODE): JsonResponse
    {
        $firstErrorMessage = collect($this->data)->first();

        return response()->json([
            'status' => $statusCode,
            'success' => false,
            'message' => $this->message ?: $firstErrorMessage[0],
            'error' => $this->data,
        ], $statusCode);
    }

    public function unAuthorizedResponse(int $statusCode = self::UNAUTHORIZED_STATUS_CODE): JsonResponse
    {
        return response()->json([
            'status' => $statusCode,
            'success' => false,
            'message' => $this->message ?: 'Unauthorized Access'
        ],$statusCode);
    }

}
