<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint for API monitoring.
     */
    public function __invoke(): JsonResponse
    {
        try {
            $dbStatus = DB::connection()->getDatabaseName() !== null;
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        return response()->json([
            'ok' => true,
            'time' => now()->toIso8601String(),
            'env' => app()->environment(),
            'db' => $dbStatus,
        ]);
    }
}