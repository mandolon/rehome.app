<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('Health Endpoints', function () {
    describe('/api/health', function () {
        it('returns health status with all services up', function () {
            $response = $this->getJson('/api/health');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'ok',
                    'data' => [
                        'ok',
                        'db',
                        'queue',
                        'version',
                        'timestamp',
                    ]
                ])
                ->assertJson([
                    'ok' => true,
                    'data' => [
                        'ok' => true,
                        'db' => true,
                        // queue may be false in test environment without Redis
                        'version' => '1.0.0',
                    ]
                ]);

            expect($response->json('data.timestamp'))->toBeString();
        });

        it('returns health status on v1 endpoint', function () {
            $response = $this->getJson('/api/v1/health');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'ok',
                    'data' => [
                        'ok',
                        'db', 
                        'queue',
                        'version',
                        'timestamp',
                    ]
                ]);
        });

        it('returns 503 when database is down', function () {
            // Mock DB connection failure
            DB::shouldReceive('connection->getPdo')
                ->andThrow(new Exception('Connection failed'));
            
            DB::shouldReceive('select')
                ->andThrow(new Exception('Query failed'));

            $response = $this->getJson('/api/health');

            $response->assertStatus(503)
                ->assertJson([
                    'ok' => true,
                    'data' => [
                        'ok' => true,
                        'db' => false,
                    ]
                ]);
        });
    });

    describe('/api/ready', function () {
        it('returns ready when all systems operational', function () {
            $response = $this->getJson('/api/ready');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'ok',
                    'data' => [
                        'ready',
                        'checks' => [
                            'database',
                            'redis',
                            'migrations',
                        ],
                        'timestamp',
                    ]
                ])
                ->assertJson([
                    'ok' => true,
                    'data' => [
                        'ready' => true,
                        'checks' => [
                            'database' => true,
                            'migrations' => true,
                            // redis may be false in test environment
                        ]
                    ]
                ]);
        });

        it('returns ready on v1 endpoint', function () {
            $response = $this->getJson('/api/v1/ready');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'ok',
                    'data' => [
                        'ready',
                        'checks',
                        'timestamp',
                    ]
                ]);
        });

        it('returns 503 when database not ready', function () {
            // Mock database connection failure
            DB::shouldReceive('connection->getPdo')
                ->andThrow(new Exception('Database not available'));

            $response = $this->getJson('/api/ready');

            $response->assertStatus(503)
                ->assertJsonStructure([
                    'ok',
                    'error',
                    'message'
                ])
                ->assertJson([
                    'ok' => false,
                    'error' => 'SERVICE_UNAVAILABLE'
                ]);

            expect($response->json('message'))->toContain('System not ready');
        });

        it('returns 503 when migrations not current', function () {
            // Mock missing migrations table
            Schema::shouldReceive('hasTable')
                ->with('migrations')
                ->andReturn(false);

            $response = $this->getJson('/api/ready');

            $response->assertStatus(503)
                ->assertJson([
                    'ok' => false,
                    'error' => 'SERVICE_UNAVAILABLE'
                ]);
        });

        it('validates migrations table has entries', function () {
            // Mock empty migrations table
            Schema::shouldReceive('hasTable')
                ->with('migrations')  
                ->andReturn(true);

            DB::shouldReceive('table')
                ->with('migrations')
                ->andReturn((object)[]);

            DB::shouldReceive('count')
                ->andReturn(0);

            $response = $this->getJson('/api/ready');

            $response->assertStatus(503);
        });
    });

    describe('Redis health checks', function () {
        it('handles Redis connection gracefully when unavailable', function () {
            // Mock Redis failure
            Redis::shouldReceive('ping')
                ->andThrow(new Exception('Redis unavailable'));

            $response = $this->getJson('/api/health');

            $response->assertStatus(503) // Should return 503 when queue is down
                ->assertJson([
                    'ok' => true,
                    'data' => [
                        'queue' => false,
                    ]
                ]);
        });

        it('shows queue as healthy when Redis available', function () {
            // Mock successful Redis ping
            Redis::shouldReceive('ping')
                ->andReturn('PONG');

            $response = $this->getJson('/api/health');

            $response->assertJson([
                'data' => [
                    'queue' => true,
                ]
            ]);
        });
    });

    describe('CI/CD integration', function () {
        it('provides consistent response format for monitoring', function () {
            $response = $this->getJson('/api/health');

            // Ensure response has all required fields for monitoring
            $data = $response->json('data');
            
            expect($data)->toHaveKeys(['ok', 'db', 'queue', 'version', 'timestamp']);
            expect($data['version'])->toBeString();
            expect($data['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z$/');
        });

        it('ready endpoint can be used for load balancer health checks', function () {
            $response = $this->getJson('/api/ready');
            
            // Should return 200 or 503, never other status codes
            expect($response->status())->toBeIn([200, 503]);
        });
    });
});
