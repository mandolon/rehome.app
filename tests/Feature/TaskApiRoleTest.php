<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\File;
use App\Models\Account;

describe('TaskApiController Role-Based Access', function () {
    beforeEach(function () {
        $this->account = Account::factory()->create();
        $this->project = Project::factory()->create(['account_id' => $this->account->id]);
        
        $this->adminUser = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'admin'
        ]);
        
        $this->teamUser = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'team'
        ]);
        
        $this->clientUser = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'client'
        ]);

        // Add team user as project member
        $this->project->members()->attach($this->teamUser->id);
    });

    it('allows admin to list all tasks across projects', function () {
        // Create another project
        $otherProject = Project::factory()->create(['account_id' => $this->account->id]);
        
        // Create tasks in different projects
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'created_by_id' => $this->adminUser->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $otherProject->id,
            'assignee_id' => $this->adminUser->id,
            'created_by_id' => $this->adminUser->id,
        ]);

        // Admin should see tasks from both projects
        $response1 = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks");

        $response2 = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$otherProject->id}/tasks");

        $response1->assertStatus(200)->assertJsonPath('ok', true);
        $response2->assertStatus(200)->assertJsonPath('ok', true);
    });

    it('restricts team user to only tasks in their member projects', function () {
        // Create another project where team user is NOT a member
        $otherProject = Project::factory()->create(['account_id' => $this->account->id]);
        
        // Team user should NOT have access to non-member project
        $response = $this->actingAs($this->teamUser)
            ->getJson("/api/v1/projects/{$otherProject->id}/tasks");

        $response->assertStatus(403);
    });

    it('shows team user only their assigned or created tasks per policy', function () {
        // Create tasks: assigned to team user, created by team user, neither
        $assignedTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->teamUser->id,
            'created_by_id' => $this->adminUser->id,
        ]);

        $createdTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'created_by_id' => $this->teamUser->id,
        ]);

        $otherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'created_by_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->teamUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data.data'); // Should see assigned + created tasks only
    });

    it('denies client access to all task routes', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
        ]);

        // Test all task endpoints
        $routes = [
            ['GET', "/api/v1/projects/{$this->project->id}/tasks"],
            ['POST', "/api/v1/projects/{$this->project->id}/tasks"],
            ['GET', "/api/v1/projects/{$this->project->id}/tasks/{$task->id}"],
            ['PATCH', "/api/v1/projects/{$this->project->id}/tasks/{$task->id}"],
            ['DELETE', "/api/v1/projects/{$this->project->id}/tasks/{$task->id}"],
        ];

        foreach ($routes as [$method, $route]) {
            $response = $this->actingAs($this->clientUser)->json($method, $route, [
                'title' => 'Test Task'
            ]);
            
            $response->assertStatus(403);
        }
    });

    it('validates task creation with 422 when title missing, 201 on success', function () {
        // Test missing title validation
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'description' => 'Task without title'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Test successful creation
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title' => 'Valid Task Title',
                'description' => 'Task with proper title',
                'status' => 'todo',
                'category' => 'Task'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Valid Task Title');
    });

    it('updates task title and status correctly with proper authorization', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->teamUser->id,
            'created_by_id' => $this->adminUser->id,
            'title' => 'Original Title',
            'status' => 'todo'
        ]);

        // Test unauthorized update (user not assigned or creator)
        $otherTeamUser = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'team'
        ]);
        $this->project->members()->attach($otherTeamUser->id);

        $response = $this->actingAs($otherTeamUser)
            ->patchJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'title' => 'Unauthorized Update',
                'status' => 'in_progress'
            ]);

        $response->assertStatus(403);

        // Test authorized update (by assignee)
        $response = $this->actingAs($this->teamUser)
            ->patchJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'status' => 'in_progress'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'in_progress');
    });

    it('attaches file to task and increments files_count', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'created_by_id' => $this->adminUser->id,
            'files_count' => 0,
        ]);

        $file = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->adminUser->id,
        ]);

        expect($task->files_count)->toBe(0);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/tasks/{$task->id}/files", [
                'file_id' => $file->id,
                'attachment_type' => 'attachment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true);

        $task->refresh();
        expect($task->files_count)->toBe(1);
    });

    it('returns grouped counts by category and status in meta', function () {
        // Create tasks with different categories and statuses
        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'category' => 'TASK/REDLINE',
            'status' => 'open',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'category' => 'TASK/REDLINE',
            'status' => 'complete',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'category' => 'PROGRESS/UPDATE',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'meta' => [
                    'counts' => [
                        'by_category',
                        'by_status'
                    ]
                ]
            ]);
    });

    it('filters tasks by multiple status values', function () {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'status' => 'open',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'status' => 'complete',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks?status[]=open&status[]=complete");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(2, 'data.data');

        // Test single status filter
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks?status[]=open");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    });

    it('filters tasks by search query', function () {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'title' => 'Electrical inspection needed',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->adminUser->id,
            'title' => 'Plumbing review required',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks?q=electrical");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(1, 'data.data');
    });
});