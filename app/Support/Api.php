<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class Api
{
    /**
     * Standard success response with consistent envelope
     */
    public static function success($data = null, string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'ok' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        // Handle pagination metadata
        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'page' => $data->currentPage(),
                'perPage' => $data->perPage(),
                'total' => $data->total(),
                'lastPage' => $data->lastPage(),
                'hasMorePages' => $data->hasMorePages(),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Legacy ok method - redirects to success for consistency
     */
    public static function ok($data = [], $meta = [], int $status = 200): JsonResponse
    {
        $response = [
            'ok' => true,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    public static function created($data = [], string $message = null): JsonResponse
    {
        return self::success($data, $message ?: 'Created successfully', 201);
    }

    public static function error(string $error, string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $error,
            'message' => $message,
        ], $status);
    }

    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error('NotFound', $message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error('Unauthorized', $message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error('Forbidden', $message, 403);
    }

    public static function validationError(string $message = 'Validation failed'): JsonResponse
    {
        return self::error('ValidationException', $message, 422);
    }
}
