<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\File;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class TaskApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|uuid|exists:projects,id',
            'category' => 'nullable|string|in:TASK/REDLINE,PROGRESS/UPDATE',
            'status' => 'nullable|string|in:open,complete',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'overdue' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $query = Task::with(['project', 'assignee', 'createdBy']);

        // Filter by user's accessible projects
        if ($user->isClient()) {
            $projectIds = $user->account->projects()
                ->where('user_id', $user->id)
                ->pluck('id');
            $query->whereIn('project_id', $projectIds)
                  ->clientVisible();
        } else {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('account_id', $user->account_id);
            });
        }

        // Apply filters
        if (isset($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        if (isset($validated['category'])) {
            $query->byCategory($validated['category']);
        }

        if (isset($validated['status'])) {
            if ($validated['status'] === 'open') {
                $query->open();
            } elseif ($validated['status'] === 'complete') {
                $query->complete();
            }
        }

        if (isset($validated['assignee_id'])) {
            $query->where('assignee_id', $validated['assignee_id']);
        }

        if (isset($validated['overdue']) && $validated['overdue']) {
            $query->overdue();
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|uuid|exists:projects,id',
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
        $project = Project::findOrFail($validated['project_id']);

        // Authorization check
        if (!Gate::allows('manage-tasks', $project)) {
            return Api::error('Unauthorized', 403);
        }

        // Validate assignee belongs to same account
        if (isset($validated['assignee_id'])) {
            $assignee = $user->account->users()->find($validated['assignee_id']);
            if (!$assignee) {
                return Api::error('Assignee must belong to the same account', 422);
            }
        }

        $task = Task::create([
            'project_id' => $validated['project_id'],
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

    public function show(string $id): JsonResponse
    {
        $task = Task::with(['project', 'assignee', 'createdBy', 'files', 'activities.user'])
                   ->findOrFail($id);

        $user = request()->user();

        // Authorization check
        if (!Gate::allows('view-task', $task)) {
            return Api::error('Unauthorized', 403);
        }

        return Api::success($task, 'Task retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!Gate::allows('manage-tasks', $task->project)) {
            return Api::error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:TASK/REDLINE,PROGRESS/UPDATE',
            'status' => 'nullable|string|in:open,complete',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date',
            'allow_client' => 'nullable|boolean',
        ]);

        // Track changes for activity log
        $changes = [];
        $originalData = $task->toArray();

        // Validate assignee belongs to same account
        if (isset($validated['assignee_id'])) {
            $assignee = $user->account->users()->find($validated['assignee_id']);
            if (!$assignee) {
                return Api::error('Assignee must belong to the same account', 422);
            }
        }

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

    public function destroy(string $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = request()->user();

        // Authorization check
        if (!Gate::allows('manage-tasks', $task->project)) {
            return Api::error('Unauthorized', 403);
        }

        $task->delete();

        return Api::success(null, 'Task deleted successfully');
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check - assignee or team member can complete
        if (!Gate::allows('complete-task', $task)) {
            return Api::error('Unauthorized', 403);
        }

        $task->markAsComplete($user);

        $task->load(['project', 'assignee', 'createdBy']);

        return Api::success($task, 'Task marked as complete');
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'assignee_id' => 'required|integer|exists:users,id',
        ]);

        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!Gate::allows('manage-tasks', $task->project)) {
            return Api::error('Unauthorized', 403);
        }

        // Validate assignee belongs to same account
        $assignee = $user->account->users()->find($validated['assignee_id']);
        if (!$assignee) {
            return Api::error('Assignee must belong to the same account', 422);
        }

        $task->assignTo($assignee, $user);

        $task->load(['project', 'assignee', 'createdBy']);

        return Api::success($task, 'Task assigned successfully');
    }

    public function addComment(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!Gate::allows('view-task', $task)) {
            return Api::error('Unauthorized', 403);
        }

        $activity = $task->addComment($user, $validated['comment']);

        $activity->load('user');

        return Api::success($activity, 'Comment added successfully', 201);
    }

    public function attachFile(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'file_id' => 'required|uuid|exists:files,id',
            'attachment_type' => 'nullable|string|in:attachment,redline,revision',
            'notes' => 'nullable|string|max:500',
        ]);

        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!Gate::allows('manage-tasks', $task->project)) {
            return Api::error('Unauthorized', 403);
        }

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

    public function detachFile(Request $request, string $id, string $fileId): JsonResponse
    {
        $task = Task::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!Gate::allows('manage-tasks', $task->project)) {
            return Api::error('Unauthorized', 403);
        }

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
