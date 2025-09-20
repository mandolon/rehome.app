<?php

use App\Models\Account;
use App\Models\File;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up test data
    $this->account = Account::factory()->create();
    
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'account_id' => $this->account->id,
    ]);
    $this->account->users()->attach($this->admin);

    $this->team = User::factory()->create([
        'role' => 'team',
        'account_id' => $this->account->id,
    ]);
    $this->account->users()->attach($this->team);

    $this->client = User::factory()->create([
        'role' => 'client',
        'account_id' => $this->account->id,
    ]);
    $this->account->users()->attach($this->client);

    // Create a project owned by admin
    $this->project = Project::factory()->create([
        'account_id' => $this->account->id,
        'user_id' => $this->admin->id,
    ]);

    // Set up fake storage
    Storage::fake('private');
});

describe('File API', function () {
    it('allows admin to access file information', function () {
        $file = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => 'account-1/project-1/document.pdf',
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    'id',
                    'name',
                    'mime_type',
                    'size',
                    'download_url',
                    'metadata',
                    'created_at'
                ]
            ])
            ->assertJson([
                'ok' => true,
                'data' => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                ]
            ]);

        // Verify download_url is a signed URL
        expect($response->json('data.download_url'))->toContain('signature=');
    });

    it('prevents client from accessing other account files', function () {
        $otherAccount = Account::factory()->create();
        $otherUser = User::factory()->create([
            'role' => 'client',
            'account_id' => $otherAccount->id,
        ]);

        $file = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
        ]);

        $token = $otherUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$file->id}");

        $response->assertStatus(403);
    });

    it('validates file paths for security', function () {
        $file = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => '../../../etc/passwd', // Directory traversal attempt
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$file->id}");

        $response->assertStatus(403)
            ->assertJson([
                'ok' => false,
                'error' => 'INVALID_FILE_PATH'
            ]);
    });

    it('requires valid signature for file download', function () {
        $file = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => 'account-1/test.pdf',
        ]);

        Storage::disk('private')->put('account-1/test.pdf', 'test file content');

        // Try to download without signature
        $response = $this->getJson("/api/v1/files/{$file->id}/download");

        $response->assertStatus(403)
            ->assertJson([
                'ok' => false,
                'error' => 'INVALID_SIGNATURE'
            ]);
    });

    it('successfully downloads file with valid signature', function () {
        $file = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => 'account-1/test.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('private')->put('account-1/test.pdf', 'test file content');

        $token = $this->admin->createToken('test')->plainTextToken;

        // First get the signed URL
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$file->id}");

        $downloadUrl = $response->json('data.download_url');
        
        // Extract the download path and signature
        $parsedUrl = parse_url($downloadUrl);
        parse_str($parsedUrl['query'], $queryParams);

        // Make authenticated download request with signature
        $downloadResponse = $this->withHeader('Authorization', "Bearer $token")
            ->get($parsedUrl['path'] . '?' . $parsedUrl['query']);

        $downloadResponse->assertStatus(200);
        expect($downloadResponse->getContent())->toBe('test file content');
    });

    it('client can only access files from owned projects', function () {
        // Create project owned by client
        $clientProject = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->client->id,
        ]);

        $accessibleFile = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $clientProject->id,
            'storage_path' => 'account-1/client-file.pdf',
        ]);

        $token = $this->client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$accessibleFile->id}");

        $response->assertStatus(200);

        // But cannot access files from admin's project
        $adminFile = File::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id, // Admin's project
            'storage_path' => 'account-1/admin-file.pdf',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/v1/files/{$adminFile->id}");

        $response->assertStatus(403);
    });
});
