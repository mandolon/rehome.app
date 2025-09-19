<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Return consistent JSON error format for API requests
        if ($request->expectsJson()) {
            $status = $this->getStatusCode($e);
            
            return response()->json([
                'ok' => false,
                'error' => class_basename($e),
                'message' => $e->getMessage(),
            ], $status);
        }

        return parent::render($request, $e);
    }

    /**
     * Get the status code for the exception.
     */
    private function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // Map common exceptions to status codes
        return match (get_class($e)) {
            'Illuminate\Auth\AuthenticationException' => 401,
            'Illuminate\Auth\Access\AuthorizationException' => 403,
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 404,
            'Illuminate\Validation\ValidationException' => 422,
            'Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException' => 429,
            default => 500,
        };
    }
}
