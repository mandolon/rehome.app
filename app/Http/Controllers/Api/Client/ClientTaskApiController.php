<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientTaskApiController extends Controller
{
    /**
     * Client-safe task index - only shows allow_client=true tasks
     * Hides sensitive internal fields
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        // Authorize client access to project
        $user = $request->user();
        
        if ($user->roleIn($project->account) !== 'client') {
            return Api::forbidden('Client access only');
        }
        
        if (!$project->hasMember($user)) {
            return Api::forbidden('Not a member of this project');
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:open,complete',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50', // Lower limit for clients
        ]);

        $query = Task::where('project_id', $project->id)
                    ->where('allow_client', true)
                    ->with(['assignee:id,name', 'createdBy:id,name']);

        // Apply filters
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')
                      ->paginate($validated['per_page'] ?? 10);

        // Transform to client-safe format
        $clientTasks = $tasks->through(function ($task) {
            return $this->transformTaskForClient($task);
        });

        return Api::success($clientTasks, 'Client tasks retrieved successfully');
    }

    /**
     * Client-safe task detail - only allow_client=true tasks
     */
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $user = $request->user();
        
        if ($user->roleIn($project->account) !== 'client') {
            return Api::forbidden('Client access only');
        }
        
        if (!$project->hasMember($user)) {
            return Api::forbidden('Not a member of this project');
        }

        if (!$task->allow_client) {
            return Api::notFound('Task not found');
        }

        $task->load([
            'assignee:id,name',
            'createdBy:id,name',
            'files:id,original_name,mime_type,size,created_at',
            'activities' => function ($query) {
                // Only show client-safe activities
                $query->where('is_system', true)
                      ->orWhere('action_type', 'comment')
                      ->with('user:id,name')
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            }
        ]);

        return Api::success(
            $this->transformTaskForClient($task),
            'Task retrieved successfully'
        );
    }

    /**
     * Transform task data for client consumption
     * Removes sensitive internal fields
     */
    private function transformTaskForClient(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'category' => $task->category,
            'status' => $task->status,
            'due_date' => $task->due_date?->toISOString(),
            'created_at' => $task->created_at->toISOString(),
            'updated_at' => $task->updated_at->toISOString(),
            
            // Safe user references
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
            ] : null,
            
            'created_by' => $task->createdBy ? [
                'id' => $task->createdBy->id,
                'name' => $task->createdBy->name,
            ] : null,
            
            // File attachments (if loaded)
            'files' => $task->relationLoaded('files') 
                ? $task->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'mime_type' => $file->mime_type,
                        'size' => $file->size,
                        'created_at' => $file->created_at->toISOString(),
                    ];
                })
                : null,
                
            // Activities (if loaded) - filtered for client safety
            'activities' => $task->relationLoaded('activities')
                ? $task->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action_type' => $activity->action_type,
                        'comment' => $activity->comment,
                        'created_at' => $activity->created_at->toISOString(),
                        'user' => [
                            'id' => $activity->user->id,
                            'name' => $activity->user->name,
                        ],
                    ];
                })
                : null,
                
            // Summary stats only (hide internal counts)
            'files_count' => $task->files_count ?? 0,
        ];
    }
}
