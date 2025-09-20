<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $admin;
    protected User $teamMember;
    protected User $client;
    protected Project $clientProject;
    protected Project $adminProject;

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

        // Create test projects
        $this->clientProject = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->client->id,
            'name' => 'Client Project',
        ]);

        $this->adminProject = Project::factory()->create([
            'account_id' => $this->account->id,
            'user_id' => $this->admin->id,
            'name' => 'Admin Project',
        ]);
    }

    public function test_admin_can_view_all_tasks()
    {
        Sanctum::actingAs($this->admin);

        // Create tasks - both client-visible and internal
        $clientVisibleTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        $internalTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        // Admin should be able to view both
        $response1 = $this->getJson("/api/v1/tasks/{$clientVisibleTask->id}");
        $response2 = $this->getJson("/api/v1/tasks/{$internalTask->id}");

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    public function test_team_member_can_view_all_tasks()
    {
        Sanctum::actingAs($this->teamMember);

        // Create tasks - both client-visible and internal
        $clientVisibleTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        $internalTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        // Team member should be able to view both
        $response1 = $this->getJson("/api/v1/tasks/{$clientVisibleTask->id}");
        $response2 = $this->getJson("/api/v1/tasks/{$internalTask->id}");

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    public function test_client_can_only_view_client_visible_tasks_on_their_projects()
    {
        Sanctum::actingAs($this->client);

        // Create tasks on client's project
        $clientVisibleTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        $internalTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        // Create task on different project
        $otherProjectTask = Task::factory()->create([
            'project_id' => $this->adminProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        // Client should only see client-visible task on their project
        $response1 = $this->getJson("/api/v1/tasks/{$clientVisibleTask->id}");
        $response2 = $this->getJson("/api/v1/tasks/{$internalTask->id}");
        $response3 = $this->getJson("/api/v1/tasks/{$otherProjectTask->id}");

        $response1->assertStatus(200);
        $response2->assertStatus(403);
        $response3->assertStatus(403);
    }

    public function test_admin_can_create_tasks()
    {
        Sanctum::actingAs($this->admin);

        $taskData = [
            'project_id' => $this->clientProject->id,
            'title' => 'Admin Created Task',
            'category' => 'TASK/REDLINE',
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(201);
    }

    public function test_team_member_can_create_tasks()
    {
        Sanctum::actingAs($this->teamMember);

        $taskData = [
            'project_id' => $this->clientProject->id,
            'title' => 'Team Created Task',
            'category' => 'TASK/REDLINE',
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(201);
    }

    public function test_client_cannot_create_tasks()
    {
        Sanctum::actingAs($this->client);

        $taskData = [
            'project_id' => $this->clientProject->id,
            'title' => 'Client Created Task',
            'category' => 'TASK/REDLINE',
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_tasks()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
        ]);

        $response->assertStatus(200);
    }

    public function test_client_cannot_update_tasks()
    {
        Sanctum::actingAs($this->client);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Client Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_tasks()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_client_cannot_delete_tasks()
    {
        Sanctum::actingAs($this->client);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_comment_on_all_tasks()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/comments", [
            'comment' => 'Admin comment',
        ]);

        $response->assertStatus(201);
    }

    public function test_client_can_comment_only_on_client_visible_tasks()
    {
        Sanctum::actingAs($this->client);

        // Client-visible task on client's project
        $clientVisibleTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        // Internal task on client's project
        $internalTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        $response1 = $this->postJson("/api/v1/tasks/{$clientVisibleTask->id}/comments", [
            'comment' => 'Client comment on visible task',
        ]);

        $response2 = $this->postJson("/api/v1/tasks/{$internalTask->id}/comments", [
            'comment' => 'Client comment on internal task',
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(403);
    }

    public function test_admin_can_attach_files_to_all_tasks()
    {
        Sanctum::actingAs($this->admin);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        $file = File::factory()->create([
            'project_id' => $this->clientProject->id,
            'uploaded_by_id' => $this->admin->id,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/files", [
            'file_id' => $file->id,
            'attachment_type' => 'attachment',
        ]);

        $response->assertStatus(201);
    }

    public function test_client_can_attach_files_only_to_client_visible_tasks()
    {
        Sanctum::actingAs($this->client);

        // Client-visible task
        $clientVisibleTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
        ]);

        // Internal task
        $internalTask = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => false,
        ]);

        $file = File::factory()->create([
            'project_id' => $this->clientProject->id,
            'uploaded_by_id' => $this->admin->id,
        ]);

        $response1 = $this->postJson("/api/v1/tasks/{$clientVisibleTask->id}/files", [
            'file_id' => $file->id,
            'attachment_type' => 'attachment',
        ]);

        $response2 = $this->postJson("/api/v1/tasks/{$internalTask->id}/files", [
            'file_id' => $file->id,
            'attachment_type' => 'attachment',
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(403);
    }

    public function test_task_assignee_can_complete_their_task()
    {
        Sanctum::actingAs($this->teamMember);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'assignee_id' => $this->teamMember->id,
            'created_by_id' => $this->admin->id,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertStatus(200);
    }

    public function test_client_cannot_complete_tasks()
    {
        Sanctum::actingAs($this->client);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
            'allow_client' => true,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/complete");

        $response->assertStatus(403);
    }

    public function test_users_from_different_accounts_cannot_access_tasks()
    {
        // Create another account and user
        $otherAccount = Account::factory()->create();
        $otherUser = User::factory()->create([
            'account_id' => $otherAccount->id,
            'role' => 'admin',
        ]);

        Sanctum::actingAs($otherUser);

        $task = Task::factory()->create([
            'project_id' => $this->clientProject->id,
            'created_by_id' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}
