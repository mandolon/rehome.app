<?php

use App\Models\User;
use App\Models\Account;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test account and users for each test
    $this->account = Account::factory()->create([
        'name' => 'Test Account',
        'slug' => 'test-account',
    ]);

    $this->admin = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'admin',
        'password' => Hash::make('password'),
    ]);

    $this->team = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'team',
        'password' => Hash::make('password'),
    ]);

    $this->client = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'client',
        'password' => Hash::make('password'),
    ]);
});

describe('Authentication', function () {
    it('can login with valid credentials', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => $this->admin->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'account_id'],
                    'token'
                ]
            ])
            ->assertJson([
                'ok' => true,
                'data' => [
                    'user' => [
                        'email' => $this->admin->email,
                        'role' => 'admin',
                    ]
                ]
            ]);

        expect($response->json('data.token'))->toBeString();
    });

    it('rejects invalid credentials', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => $this->admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    });

    it('can get user information with valid token', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'user' => [
                        'id' => $this->admin->id,
                        'email' => $this->admin->email,
                        'role' => 'admin',
                    ]
                ]
            ]);
    });

    it('rejects requests without token', function () {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    });

    it('can logout and revoke token', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/logout');

        $response->assertOk()
            ->assertJson(['ok' => true]);

        // Token should now be invalid
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/me');

        $response->assertStatus(401);
    });
});

describe('Projects API', function () {
    it('admin can create projects', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/projects', [
                'name' => 'Test Project',
                'description' => 'A test project',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'ok',
                'data' => ['id', 'name', 'description', 'status'],
                'message'
            ])
            ->assertJson([
                'ok' => true,
                'data' => [
                    'name' => 'Test Project',
                    'status' => 'active',
                ]
            ]);
    });

    it('team members can create projects', function () {
        $token = $this->team->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/projects', [
                'name' => 'Team Project',
                'description' => 'A project by team member',
            ]);

        $response->assertStatus(201);
    });

    it('clients cannot create projects', function () {
        $token = $this->client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/projects', [
                'name' => 'Client Project',
                'description' => 'Should not be allowed',
            ]);

        $response->assertStatus(403);
    });

    it('can list projects with pagination', function () {
        // Create multiple projects
        Project::factory()->count(25)->create([
            'account_id' => $this->account->id,
            'user_id' => $this->admin->id,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/projects?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'data' => [
                    '*' => ['id', 'name', 'status', 'created_at']
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ])
            ->assertJson([
                'ok' => true,
                'meta' => [
                    'per_page' => 10,
                    'total' => 25,
                ]
            ]);
    });

    it('enforces account isolation', function () {
        // Create project in different account
        $otherAccount = Account::factory()->create();
        $otherProject = Project::factory()->create([
            'account_id' => $otherAccount->id,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        // Should not see other account's projects
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/projects');

        $response->assertOk();
        
        $projectIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($projectIds)->not->toContain($otherProject->id);
    });

    it('clients only see their own projects', function () {
        // Create projects owned by different users
        $adminProject = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->admin->id,
        ]);

        $clientProject = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->client->id,
        ]);

        $token = $this->client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/projects');

        $response->assertOk();
        
        $projectIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($projectIds)->toContain($clientProject->id);
        expect($projectIds)->not->toContain($adminProject->id);
    });
});

describe('Rate Limiting', function () {
    it('enforces API rate limits', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        // Make many requests quickly
        for ($i = 0; $i < 125; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->getJson('/api/v1/me');

            if ($response->status() === 429) {
                expect($i)->toBeGreaterThan(120); // Should hit limit after 120 requests
                return;
            }
        }

        $this->fail('Rate limit was not enforced');
    });

    it('enforces auth rate limits', function () {
        // Make many login attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);

            if ($response->status() === 429) {
                expect($i)->toBeGreaterThan(5); // Should hit limit after 5 requests
                return;
            }
        }

        $this->fail('Auth rate limit was not enforced');
    });
});

describe('Error Handling', function () {
    it('returns consistent error format', function () {
        $response = $this->getJson('/api/v1/projects/999999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'ok',
                'error',
                'message'
            ])
            ->assertJson([
                'ok' => false,
                'error' => 'ModelNotFoundException'
            ]);
    });

    it('handles validation errors consistently', function () {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/projects', []); // Missing required 'name' field

        $response->assertStatus(422)
            ->assertJsonStructure([
                'ok',
                'error',
                'message'
            ])
            ->assertJson([
                'ok' => false,
                'error' => 'ValidationException'
            ]);
    });
});
