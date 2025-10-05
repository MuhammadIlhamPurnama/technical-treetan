<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success(
        string $message = 'Success',
        array $data = [],
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'Error occurred',
        array $errors = [],
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Server error response
     */
    public static function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}