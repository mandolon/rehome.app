<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\File;
use App\Models\TaskFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFileCountTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $user;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create(['account_id' => $this->account->id]);
        $this->project = Project::factory()->create(['account_id' => $this->account->id]);
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by_id' => $this->user->id,
            'files_count' => 0,
        ]);
    }

    public function test_files_count_increments_when_taskfile_created()
    {
        // Create a file
        $file = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        // Initial count should be 0
        $this->assertEquals(0, $this->task->files_count);

        // Create TaskFile - should automatically increment files_count
        TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'attachment',
        ]);

        // Refresh the task and check count
        $this->task->refresh();
        $this->assertEquals(1, $this->task->files_count);
    }

    public function test_files_count_decrements_when_taskfile_deleted()
    {
        // Create a file and taskfile
        $file = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        $taskFile = TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'attachment',
        ]);

        // Refresh and check count is 1
        $this->task->refresh();
        $this->assertEquals(1, $this->task->files_count);

        // Delete the TaskFile - should automatically decrement files_count
        $taskFile->delete();

        // Refresh and check count is back to 0
        $this->task->refresh();
        $this->assertEquals(0, $this->task->files_count);
    }

    public function test_multiple_files_count_correctly()
    {
        // Create multiple files
        $file1 = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        $file2 = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        $file3 = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        // Create multiple TaskFiles
        $taskFile1 = TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file1->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'attachment',
        ]);

        $taskFile2 = TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file2->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'redline',
        ]);

        $taskFile3 = TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file3->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'revision',
        ]);

        // Check count is 3
        $this->task->refresh();
        $this->assertEquals(3, $this->task->files_count);

        // Delete one file
        $taskFile2->delete();

        // Check count is 2
        $this->task->refresh();
        $this->assertEquals(2, $this->task->files_count);

        // Delete remaining files
        $taskFile1->delete();
        $taskFile3->delete();

        // Check count is 0
        $this->task->refresh();
        $this->assertEquals(0, $this->task->files_count);
    }

    public function test_task_relationships_work_correctly()
    {
        // Test Task belongs to Project
        $this->assertEquals($this->project->id, $this->task->project->id);

        // Test Task belongs to creator (User)
        $this->assertEquals($this->user->id, $this->task->createdBy->id);
        $this->assertEquals($this->user->id, $this->task->creator->id);

        // Assign the task
        $assignee = User::factory()->create(['account_id' => $this->account->id]);
        $this->task->update(['assignee_id' => $assignee->id]);

        // Test Task belongs to assignee (User)
        $this->assertEquals($assignee->id, $this->task->assignee->id);

        // Create TaskFile and test relationships
        $file = File::factory()->create([
            'project_id' => $this->project->id,
            'uploaded_by_id' => $this->user->id,
        ]);

        $taskFile = TaskFile::create([
            'task_id' => $this->task->id,
            'file_id' => $file->id,
            'added_by_id' => $this->user->id,
            'attachment_type' => 'attachment',
        ]);

        // Test TaskFile belongs to Task
        $this->assertEquals($this->task->id, $taskFile->task->id);

        // Test TaskFile belongs to File
        $this->assertEquals($file->id, $taskFile->file->id);

        // Test Task hasMany TaskFiles
        $this->assertCount(1, $this->task->taskFiles);
        $this->assertEquals($taskFile->id, $this->task->taskFiles->first()->id);

        // Create TaskActivity and test relationships
        $activity = $this->task->activities()->create([
            'user_id' => $this->user->id,
            'action_type' => 'commented',
            'comment' => 'Test comment',
            'is_system' => false,
        ]);

        // Test TaskActivity belongs to Task
        $this->assertEquals($this->task->id, $activity->task->id);

        // Test TaskActivity belongs to User
        $this->assertEquals($this->user->id, $activity->user->id);

        // Test Task hasMany activities
        $this->assertCount(1, $this->task->activities);
        $this->assertEquals($activity->id, $this->task->activities->first()->id);

        // Test the activity alias
        $this->assertCount(1, $this->task->activity);
        $this->assertEquals($activity->id, $this->task->activity->first()->id);
    }
}
