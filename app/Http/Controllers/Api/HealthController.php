<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Exception;

class HealthController extends Controller
{
    /**
     * Health check endpoint - returns current system status
     */
    public function health()
    {
        $health = [
            'ok' => true,
            'db' => $this->checkDatabase(),
            'queue' => $this->checkRedis(),
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString(),
        ];

        // If any critical service is down, return 503
        $status = ($health['db'] && $health['queue']) ? 200 : 503;

        return Api::ok($health, [], $status);
    }

    /**
     * Readiness check - only returns 200 if all systems ready
     */
    public function ready()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'migrations' => $this->checkMigrations(),
        ];

        if (!$checks['database'] || !$checks['redis'] || !$checks['migrations']) {
            return Api::error(
                'SERVICE_UNAVAILABLE',
                'System not ready: ' . json_encode(array_filter($checks, fn($v) => !$v)),
                503
            );
        }

        return Api::ok([
            'ready' => true,
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function checkMigrations(): bool
    {
        try {
            // Check if migrations table exists and has entries
            if (!Schema::hasTable('migrations')) {
                return false;
            }

            $migrationsCount = DB::table('migrations')->count();
            
            // Should have at least the basic tables migrated
            return $migrationsCount > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
