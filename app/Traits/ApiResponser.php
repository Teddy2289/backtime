<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Success response format.
     */
    protected function successResponse($data = null, string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        // Remove null values
        $response = array_filter($response, function ($value) {
            return $value !== null;
        });

        return response()->json($response, $code);
    }

    /**
     * Error response format.
     */
    protected function errorResponse(string $message = null, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message ?? 'An error occurred',
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ];

        // Remove null values
        $response = array_filter($response, function ($value) {
            return $value !== null;
        });

        return response()->json($response, $code);
    }

    /**
     * Authentication response format.
     */
    /**
     * Authentication response format.
     */
    protected function authResponse(string $token, $user = null, string $message = null): JsonResponse
    {
        // Récupérer le TTL depuis la config
        $ttl = config('jwt.ttl', 60); // default 60 minutes

        $response = [
            'success' => true,
            'message' => $message,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
                'expires_in_minutes' => $ttl,
                'expires_in_hours' => $ttl / 60,
                'user' => $user,
            ],
            'timestamp' => now()->toISOString(),
        ];

        // Remove null values
        $response = array_filter($response, function ($value) {
            return $value !== null;
        });

        return response()->json($response, 200);
    }
    /**
     * Paginated response format.
     */
    protected function paginatedResponse($paginatedData, string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginatedData->items(),
            'meta' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
            ],
            'links' => [
                'first' => $paginatedData->url(1),
                'last' => $paginatedData->url($paginatedData->lastPage()),
                'prev' => $paginatedData->previousPageUrl(),
                'next' => $paginatedData->nextPageUrl(),
            ],
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, 200);
    }

    /**
     * Resource not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Validation error response.
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }
}
