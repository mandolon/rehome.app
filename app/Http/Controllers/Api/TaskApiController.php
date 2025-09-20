<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\File;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskApiController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $validated = $request->validate([
            'category' => 'nullable|string|in:TASK/REDLINE,PROGRESS/UPDATE',
            'status' => 'nullable|string|in:open,complete',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'overdue' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $role = $user->roleIn($project->account);
        
        $query = Task::where('project_id', $project->id)
                    ->with(['project', 'assignee', 'createdBy']);

        // Role-based task filtering
        if ($role === 'team') {
            // team sees only tasks assigned to them (or created by them)
            $query->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                  ->orWhere('created_by_id', $user->id);
            });
        }
        // admin: no extra filter - sees all tasks in project

        // Apply additional filters
        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['assignee_id'])) {
            $query->where('assignee_id', $validated['assignee_id']);
        }

        if (isset($validated['overdue']) && $validated['overdue']) {
            $query->where('due_date', '<', now())
                  ->where('status', '!=', 'complete');
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')
                      ->paginate($validated['per_page'] ?? 20);

        return Api::success($tasks, 'Tasks retrieved successfully');
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:TASK/REDLINE,PROGRESS/UPDATE',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'allow_client' => 'nullable|boolean',
            'file_ids' => 'nullable|array',
            'file_ids.*' => 'uuid|exists:files,id',
        ]);

        $user = $request->user();

        // Validate assignee belongs to same account
        if (isset($validated['assignee_id'])) {
            $assignee = $user->account->users()->find($validated['assignee_id']);
            if (!$assignee) {
                return Api::error('Assignee must belong to the same account', 422);
            }
        }

        $task = Task::create([
            'project_id' => $project->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'assignee_id' => $validated['assignee_id'],
            'created_by_id' => $user->id,
            'due_date' => $validated['due_date'],
            'allow_client' => $validated['allow_client'] ?? false,
        ]);

        // Log creation activity
        $task->activities()->create([
            'user_id' => $user->id,
            'action_type' => 'created',
            'comment' => 'Task created',
            'is_system' => true,
        ]);

        // Attach files if provided
        if (isset($validated['file_ids'])) {
            foreach ($validated['file_ids'] as $fileId) {
                $file = File::find($fileId);
                if ($file && $file->project_id === $task->project_id) {
                    $task->attachFile($file, $user);
                }
            }
        }

        $task->load(['project', 'assignee', 'createdBy', 'files']);

        return Api::success($task, 'Task created successfully', 201);
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load([
            'project', 
            'assignee', 
            'createdBy', 
            'files',
            'activities' => function ($query) {
                $query->with('user:id,name')
                      ->orderBy('created_at', 'desc')
                      ->limit(20); // Limit for performance
            }
        ]);

        return Api::success($task, 'Task retrieved successfully');
    }

    public function update(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:TASK/REDLINE,PROGRESS/UPDATE',
            'status' => 'nullable|string|in:open,complete',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date',
            'allow_client' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Validate assignee belongs to same account
        if (isset($validated['assignee_id'])) {
            $assignee = $user->account->users()->find($validated['assignee_id']);
            if (!$assignee) {
                return Api::error('Assignee must belong to the same account', 422);
            }
        }

        // Track changes for activity log
        $changes = [];
        $originalData = $task->toArray();

        $task->fill($validated);

        // Track significant changes
        foreach (['title', 'status', 'category', 'assignee_id', 'due_date'] as $field) {
            if ($task->isDirty($field)) {
                $changes[$field] = [
                    'old' => $originalData[$field],
                    'new' => $task->{$field}
                ];
            }
        }

        $task->save();

        // Log update activity if there were changes
        if (!empty($changes)) {
            $task->activities()->create([
                'user_id' => $user->id,
                'action_type' => 'updated',
                'comment' => 'Task updated',
                'metadata' => $changes,
                'is_system' => true,
            ]);
        }

        $task->load(['project', 'assignee', 'createdBy', 'files']);

        return Api::success($task, 'Task updated successfully');
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return Api::success(null, 'Task deleted successfully');
    }

    public function complete(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('complete', $task);

        $user = $request->user();
        $task->markAsComplete($user);

        $task->load(['project', 'assignee', 'createdBy']);

        return Api::success($task, 'Task marked as complete');
    }

    public function assign(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'assignee_id' => 'required|integer|exists:users,id',
        ]);

        $user = $request->user();

        // Validate assignee belongs to same account
        $assignee = $user->account->users()->find($validated['assignee_id']);
        if (!$assignee) {
            return Api::error('Assignee must belong to the same account', 422);
        }

        $task->assignTo($assignee, $user);

        $task->load(['project', 'assignee', 'createdBy']);

        return Api::success($task, 'Task assigned successfully');
    }

    public function addComment(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $activity = $task->addComment($user, $validated['comment']);

        $activity->load('user');

        return Api::success($activity, 'Comment added successfully', 201);
    }

    public function attachFile(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('attachFile', $task);

        $validated = $request->validate([
            'file_id' => 'required|uuid|exists:files,id',
            'attachment_type' => 'nullable|string|in:attachment,redline,revision',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $file = File::findOrFail($validated['file_id']);

        // Ensure file belongs to the same project
        if ($file->project_id !== $task->project_id) {
            return Api::error('File must belong to the same project as the task', 422);
        }

        $taskFile = $task->attachFile(
            $file,
            $user,
            $validated['attachment_type'] ?? 'attachment',
            $validated['notes']
        );

        $taskFile->load(['file', 'addedBy']);

        return Api::success($taskFile, 'File attached successfully', 201);
    }

    public function detachFile(Request $request, Project $project, Task $task, string $fileId): JsonResponse
    {
        $this->authorize('attachFile', $task);

        $user = $request->user();
        $taskFile = $task->taskFiles()->where('file_id', $fileId)->first();

        if (!$taskFile) {
            return Api::error('File not found in task', 404);
        }

        $fileName = $taskFile->file->original_name;
        $taskFile->delete();

        // Log activity
        $task->activities()->create([
            'user_id' => $user->id,
            'action_type' => 'file_removed',
            'comment' => "Removed file: {$fileName}",
            'metadata' => [
                'file_name' => $fileName,
            ],
            'is_system' => true,
        ]);

        // files_count will be decremented automatically by TaskFile model events

        return Api::success(null, 'File detached successfully');
    }
}
