<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Project::query()
            ->where('account_id', $user->account_id)
            ->with(['account', 'owner']);

        // Search functionality
        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Role-based filtering
        if ($user->role === 'client') {
            // Clients can only see projects they own or are assigned to
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('users', function ($subQ) use ($user) {
                      $subQ->where('user_id', $user->id);
                  });
            });
        }

        // Ordering
        $query->orderBy('updated_at', 'desc');

        $perPage = min($request->get('per_page', 20), 50); // Max 50 per page
        $projects = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $projects->items(),
            'meta' => [
                'page' => $projects->currentPage(),
                'perPage' => $projects->perPage(),
                'total' => $projects->total(),
                'lastPage' => $projects->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,archived',
        ]);

        $project = Project::create([
            ...$validated,
            'account_id' => $request->user()->account_id,
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'active',
        ]);

        $project->load(['account', 'owner']);

        return response()->json([
            'data' => $project,
            'message' => 'Project created successfully',
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['account', 'owner', 'documents', 'tasks']);

        return response()->json([
            'data' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive,archived',
        ]);

        $project->update($validated);
        $project->load(['account', 'owner']);

        return response()->json([
            'data' => $project,
            'message' => 'Project updated successfully',
        ]);
    }

    public function destroy(Request $request, Project $project)
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }
}
