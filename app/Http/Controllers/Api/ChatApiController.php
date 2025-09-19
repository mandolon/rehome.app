<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatApiController extends Controller
{
    public function __construct(
        private RagService $ragService
    ) {}

    public function ask(Request $request, Project $project)
    {
        Gate::authorize('view', $project);

        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'context_limit' => 'integer|min:1|max:10',
        ]);

        try {
            $response = $this->ragService->askQuestion(
                $project,
                $validated['question'],
                $validated['context_limit'] ?? 3
            );

            return response()->json([
                'data' => [
                    'answer' => $response['answer'],
                    'sources' => $response['sources'],
                    'confidence' => $response['confidence'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process question',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
