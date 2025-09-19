<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class Api
{
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
        $response = [
            'ok' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, 201);
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
