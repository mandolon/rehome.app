<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TaskApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Account $account;
    protected User $admin;
    protected User $teamMember;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test account
        $this->account = Account::factory()->create([
            'name' => 'Test Construction Co',
            'slug' => 'test-construction',
        ]);

        // Create test users
        $this->admin = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $this->teamMember = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'team',
            'email' => 'team@test.com',
        ]);

        $this->client = User::factory()->create([
            'account_id' => $this->account->id,
            'role' => 'client',
            'email' => 'client@test.com',
        ]);

        // Create test project
        $this->project = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->client->id,
            'name' => 'Test Development Project',
        ]);
    }

    public function test_can_list_tasks()
    {
        Sanctum::actingAs($this->admin);

        // Create some test tasks
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'project_id',
                                'title',
                                'description',
                                'category',
                                'status',
                                'assignee_id',
                                'created_by_id',
                                'due_date',
                                'allow_client',
                                'files_count',
                                'comments_count',
                                'created_at',
                                'updated_at',
                                'project',
                                'assignee',
                                'created_by',
                            ]
                        ]
                    ]
                ]);

        $this->assertEquals(3, $response->json('data.data.0.project.id') ? 1 : 0 + 
                               $response->json('data.data.1.project.id') ? 1 : 0 + 
                               $response->json('data.data.2.project.id') ? 1 : 0);
    }

    public function test_can_create_task()
    {
        Sanctum::actingAs($this->admin);

        $taskData = [
            'project_id' => $this->project->id,
            'title' => 'New Task Title',
            'description' => 'Task description here',
            'category' => 'TASK/REDLINE',
            'assignee_id' => $this->teamMember->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'allow_client' => true,
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task created successfully',
                    'data' => [
                        'title' => 'New Task Title',
                        'category' => 'TASK/REDLINE',
                        'status' => 'open',
                        'assignee_id' => $this->teamMember->id,
                        'created_by_id' => $this->admin->id,
                        'allow_client' => true,
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task Title',
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
        ]);

        // Check that creation activity was logged
        $task = Task::where('title', 'New Task Title')->first();
        $this->assertDatabaseHas('task_activity', [
            'task_id' => $task->id,
            'action_type' => 'created',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_can_view_task()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'title' => 'Test Task',
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $task->id,
                        'title' => 'Test Task',
                        'project_id' => $this->project->id,
                    ]
                ]);
    }

    public function test_can_update_task()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'complete',
        ];

        $response = $this->putJson("/api/v1/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task updated successfully',
                    'data' => [
                        'title' => 'Updated Title',
                        'status' => 'complete',
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'complete',
        ]);

        // Check that update activity was logged
        $this->assertDatabaseHas('task_activity', [
            'task_id' => $task->id,
            'action_type' => 'updated',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_can_delete_task()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task deleted successfully',
                ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_can_complete_task()
    {
        Sanctum::actingAs($this->teamMember);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->teamMember->id,
            'created_by_id' => $this->admin->id,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task marked as complete',
                    'data' => [
                        'status' => 'complete',
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'complete',
        ]);

        // Check completion activity was logged
        $this->assertDatabaseHas('task_activity', [
            'task_id' => $task->id,
            'action_type' => 'completed',
            'user_id' => $this->teamMember->id,
        ]);
    }

    public function test_can_assign_task()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'assignee_id' => null,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/assign", [
            'assignee_id' => $this->teamMember->id,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Task assigned successfully',
                    'data' => [
                        'assignee_id' => $this->teamMember->id,
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assignee_id' => $this->teamMember->id,
        ]);

        // Check assignment activity was logged
        $this->assertDatabaseHas('task_activity', [
            'task_id' => $task->id,
            'action_type' => 'assigned',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_can_add_comment_to_task()
    {
        Sanctum::actingAs($this->teamMember);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'assignee_id' => $this->teamMember->id,
            'comments_count' => 0,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'comment' => 'This is a test comment',
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'data' => [
                        'comment' => 'This is a test comment',
                        'action_type' => 'commented',
                        'user_id' => $this->teamMember->id,
                        'is_system' => false,
                    ]
                ]);

        $this->assertDatabaseHas('task_activity', [
            'task_id' => $task->id,
            'action_type' => 'commented',
            'comment' => 'This is a test comment',
            'user_id' => $this->teamMember->id,
            'is_system' => false,
        ]);

        // Check comments count was incremented
        $task->refresh();
        $this->assertEquals(1, $task->comments_count);
    }

    public function test_client_can_only_see_client_visible_tasks()
    {
        Sanctum::actingAs($this->client);

        // Create tasks - some client visible, some not
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200);
        
        $tasks = $response->json('data.data');
        $this->assertCount(1, $tasks);
        $this->assertTrue($tasks[0]['allow_client']);
    }

    public function test_client_cannot_create_task()
    {
        Sanctum::actingAs($this->client);

        $taskData = [
            'project_id' => $this->project->id,
            'title' => 'Client Task',
            'category' => 'TASK/REDLINE',
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized',
                ]);
    }

    public function test_cannot_assign_user_from_different_account()
    {
        Sanctum::actingAs($this->admin);

        // Create user from different account
        $otherAccount = Account::factory()->create();
        $otherUser = User::factory()->create(['account_id' => $otherAccount->id]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/assign", [
            'assignee_id' => $otherUser->id,
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Assignee must belong to the same account',
                ]);
    }

    public function test_can_filter_tasks_by_category()
    {
        Sanctum::actingAs($this->admin);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'category' => 'TASK/REDLINE',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'category' => 'PROGRESS/UPDATE',
        ]);

        $response = $this->getJson('/api/v1/tasks?category=TASK/REDLINE');

        $response->assertStatus(200);
        $tasks = $response->json('data.data');
        $this->assertCount(1, $tasks);
        $this->assertEquals('TASK/REDLINE', $tasks[0]['category']);
    }

    public function test_can_filter_tasks_by_status()
    {
        Sanctum::actingAs($this->admin);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'status' => 'open',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'status' => 'complete',
        ]);

        $response = $this->getJson('/api/v1/tasks?status=open');

        $response->assertStatus(200);
        $tasks = $response->json('data.data');
        $this->assertCount(1, $tasks);
        $this->assertEquals('open', $tasks[0]['status']);
    }

    public function test_can_search_tasks()
    {
        Sanctum::actingAs($this->admin);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'title' => 'Zoning Review Task',
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->admin->id,
            'title' => 'Building Code Analysis',
        ]);

        $response = $this->getJson('/api/v1/tasks?search=zoning');

        $response->assertStatus(200);
        $tasks = $response->json('data.data');
        $this->assertCount(1, $tasks);
        $this->assertStringContainsString('Zoning', $tasks[0]['title']);
    }
}
