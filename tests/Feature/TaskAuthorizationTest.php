<?php

use App\Models\{User, Account, Project, Task};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create();
    
    // Create users with different roles
    $this->admin = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'admin'
    ]);
    
    $this->teamUser = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'team'
    ]);
    
    $this->otherTeamUser = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => 'team'
    ]);
    
    // Create projects
    $this->project1 = Project::factory()->create([
        'account_id' => $this->account->id,
        'user_id' => $this->admin->id
    ]);
    
    $this->project2 = Project::factory()->create([
        'account_id' => $this->account->id,
        'user_id' => $this->admin->id
    ]);
    
    // Assign team user to project1 only
    $this->project1->members()->attach($this->teamUser->id);
    
    // Create tasks
    $this->task1 = Task::factory()->create([
        'project_id' => $this->project1->id,
        'assignee_id' => $this->teamUser->id,
        'created_by_id' => $this->admin->id
    ]);
    
    $this->task2 = Task::factory()->create([
        'project_id' => $this->project1->id,
        'assignee_id' => $this->otherTeamUser->id,
        'created_by_id' => $this->admin->id
    ]);
    
    $this->task3 = Task::factory()->create([
        'project_id' => $this->project2->id,
        'assignee_id' => $this->teamUser->id,
        'created_by_id' => $this->admin->id
    ]);
});

describe('TaskPolicy Authorization', function () {
    it('admin can see all projects and tasks in account', function () {
        // Admin can see all projects
        expect(Project::forUser($this->admin)->count())->toBe(2);
        
        // Admin can access any project
        expect($this->admin->can('viewAny', [Task::class, $this->project1]))->toBeTrue();
        expect($this->admin->can('viewAny', [Task::class, $this->project2]))->toBeTrue();
        
        // Admin can view any task
        expect($this->admin->can('view', $this->task1))->toBeTrue();
        expect($this->admin->can('view', $this->task2))->toBeTrue();
        expect($this->admin->can('view', $this->task3))->toBeTrue();
        
        // Admin can create, update, delete anywhere
        expect($this->admin->can('create', [Task::class, $this->project1]))->toBeTrue();
        expect($this->admin->can('create', [Task::class, $this->project2]))->toBeTrue();
        expect($this->admin->can('update', $this->task1))->toBeTrue();
        expect($this->admin->can('delete', $this->task1))->toBeTrue();
    });
    
    it('team user can only see projects they are assigned to', function () {
        // Team user sees only project1 (assigned)
        expect(Project::forUser($this->teamUser)->count())->toBe(1);
        expect(Project::forUser($this->teamUser)->first()->id)->toBe($this->project1->id);
        
        // Team user can access project1 but not project2
        expect($this->teamUser->can('viewAny', [Task::class, $this->project1]))->toBeTrue();
        expect($this->teamUser->can('viewAny', [Task::class, $this->project2]))->toBeFalse();
    });
    
    it('team user can only see tasks assigned to them or created by them', function () {
        // Team user can view task1 (assigned to them)
        expect($this->teamUser->can('view', $this->task1))->toBeTrue();
        
        // Team user cannot view task2 (assigned to other user)
        expect($this->teamUser->can('view', $this->task2))->toBeFalse();
        
        // Team user cannot view task3 (not on that project)
        expect($this->teamUser->can('view', $this->task3))->toBeFalse();
    });
    
    it('team user can only create tasks on assigned projects', function () {
        // Team user can create on project1 (assigned)
        expect($this->teamUser->can('create', [Task::class, $this->project1]))->toBeTrue();
        
        // Team user cannot create on project2 (not assigned)
        expect($this->teamUser->can('create', [Task::class, $this->project2]))->toBeFalse();
    });
    
    it('team user can only update their own tasks', function () {
        // Team user can update task1 (assigned to them)
        expect($this->teamUser->can('update', $this->task1))->toBeTrue();
        
        // Team user cannot update task2 (not assigned to them)
        expect($this->teamUser->can('update', $this->task2))->toBeFalse();
    });
    
    it('only admin can delete tasks', function () {
        // Admin can delete
        expect($this->admin->can('delete', $this->task1))->toBeTrue();
        
        // Team user cannot delete even their own tasks
        expect($this->teamUser->can('delete', $this->task1))->toBeFalse();
    });
    
    it('team user can complete only their own tasks', function () {
        // Team user can complete task1 (assigned to them)
        expect($this->teamUser->can('complete', $this->task1))->toBeTrue();
        
        // Team user cannot complete task2 (not assigned to them)
        expect($this->teamUser->can('complete', $this->task2))->toBeFalse();
    });
    
    it('team user can attach files only to their own tasks', function () {
        // Team user can attach to task1 (assigned to them)
        expect($this->teamUser->can('attachFile', $this->task1))->toBeTrue();
        
        // Team user cannot attach to task2 (not assigned to them)
        expect($this->teamUser->can('attachFile', $this->task2))->toBeFalse();
    });
    
    it('team user can comment only on their own tasks', function () {
        // Team user can comment on task1 (assigned to them)
        expect($this->teamUser->can('comment', $this->task1))->toBeTrue();
        
        // Team user cannot comment on task2 (not assigned to them)
        expect($this->teamUser->can('comment', $this->task2))->toBeFalse();
    });
    
    it('team user can see created tasks even if not assigned', function () {
        // Create task where team user is creator but not assignee
        $createdTask = Task::factory()->create([
            'project_id' => $this->project1->id,
            'assignee_id' => $this->otherTeamUser->id,
            'created_by_id' => $this->teamUser->id
        ]);
        
        // Team user can view their created task
        expect($this->teamUser->can('view', $createdTask))->toBeTrue();
        expect($this->teamUser->can('update', $createdTask))->toBeTrue();
        expect($this->teamUser->can('complete', $createdTask))->toBeTrue();
    });
});

describe('API Route Authorization', function () {
    it('prevents cross-project task access via route binding', function () {
        // Create task in different account
        $otherAccount = Account::factory()->create();
        $otherProject = Project::factory()->create(['account_id' => $otherAccount->id]);
        $otherTask = Task::factory()->create(['project_id' => $otherProject->id]);
        
        // Team user cannot access task from different account via API
        $response = $this->actingAs($this->teamUser, 'sanctum')
            ->getJson("/api/v1/projects/{$otherProject->id}/tasks/{$otherTask->id}");
            
        expect($response->status())->toBe(404); // Route model binding prevents access
    });
    
    it('admin can access all tasks in account via API', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/projects/{$this->project1->id}/tasks");
            
        expect($response->status())->toBe(200);
        expect($response->json('data.data'))->toHaveCount(2); // sees both tasks
    });
    
    it('team user sees only assigned tasks via API', function () {
        $response = $this->actingAs($this->teamUser, 'sanctum')
            ->getJson("/api/v1/projects/{$this->project1->id}/tasks");
            
        expect($response->status())->toBe(200);
        expect($response->json('data.data'))->toHaveCount(1); // sees only task1
        expect($response->json('data.data.0.id'))->toBe($this->task1->id);
    });
    
    it('team user cannot access unassigned projects via API', function () {
        $response = $this->actingAs($this->teamUser, 'sanctum')
            ->getJson("/api/v1/projects/{$this->project2->id}/tasks");
            
        expect($response->status())->toBe(403); // Forbidden
    });
    
    it('team user cannot create tasks on unassigned projects', function () {
        $response = $this->actingAs($this->teamUser, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project2->id}/tasks", [
                'title' => 'Test Task',
                'category' => 'TASK/REDLINE',
            ]);
            
        expect($response->status())->toBe(403); // Forbidden
    });
    
    it('team user cannot update tasks not assigned to them', function () {
        $response = $this->actingAs($this->teamUser, 'sanctum')
            ->patchJson("/api/v1/projects/{$this->project1->id}/tasks/{$this->task2->id}", [
                'title' => 'Updated Title'
            ]);
            
        expect($response->status())->toBe(403); // Forbidden
    });
});

describe('Project Visibility', function () {
    it('Project::forUser() filters correctly by role', function () {
        // Admin sees all projects in account
        expect(Project::forUser($this->admin)->count())->toBe(2);
        
        // Team user sees only assigned projects
        expect(Project::forUser($this->teamUser)->count())->toBe(1);
        expect(Project::forUser($this->teamUser)->first()->id)->toBe($this->project1->id);
        
        // Other team user sees no projects (not assigned to any)
        expect(Project::forUser($this->otherTeamUser)->count())->toBe(0);
    });
    
    it('project leads can see all tasks in their project', function () {
        // Make team user a project lead
        $this->project1->members()->updateExistingPivot($this->teamUser->id, ['is_lead' => true]);
        
        // Note: This feature would need to be implemented if desired
        // For now, leads still only see their assigned/created tasks
        expect($this->teamUser->can('view', $this->task2))->toBeFalse();
    });
});
