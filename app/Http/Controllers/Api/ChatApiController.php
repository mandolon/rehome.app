<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatApiController extends Controller
{
    public function __construct(
        private EmbeddingService $embeddingService,
        private VectorSearchService $vectorSearchService
    ) {}

    public function ask(Request $request, Project $project)
    {
        // Authorize user access to project
        Gate::authorize('view', $project);

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        try {
            // Step 1: Embed the user's message
            $messageEmbedding = $this->embeddingService->generateEmbedding($validated['message']);

            // Step 2: Find top 12 relevant chunks using vector search
            $relevantChunks = $this->vectorSearchService->findRelevantChunks(
                $project, 
                $messageEmbedding, 
                12
            );

            // Step 3: Check if we have sufficient context
            if ($relevantChunks->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Insufficient context available in project documents to answer your question.',
                ], 422);
            }

            // Step 4: Build context from relevant chunks
            $context = $this->vectorSearchService->buildContext($relevantChunks);

            // Step 5: Call GPT-4o with system prompt
            $answer = $this->generateAnswer($validated['message'], $context);

            // Step 6: Format citations
            $citations = $this->vectorSearchService->formatCitations($relevantChunks);

            return response()->json([
                'ok' => true,
                'answer' => $answer,
                'citations' => $citations,
            ]);

        } catch (\Exception $e) {
            Log::error('RAG chat error', [
                'project_id' => $project->id,
                'message' => $validated['message'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Failed to process question',
                'message' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }

    private function generateAnswer(string $question, string $context): string
    {
        $systemPrompt = "You are a knowledgeable assistant for a preconstruction platform. " .
                       "Answer questions based strictly on the provided document context. " .
                       "Be concise, accurate, and helpful. If the context doesn't contain " .
                       "sufficient information to fully answer the question, acknowledge this. " .
                       "Focus on construction, zoning, permits, and project-related information.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('app.openai_api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Context:\n{$context}\n\nQuestion: {$question}"],
                ],
                'max_tokens' => 800,
                'temperature' => 0.3, // Lower temperature for more consistent, factual responses
            ]);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content'));
            }

            Log::error('OpenAI GPT-4o API error: ' . $response->body());
            return 'I encountered an error while generating the response. Please try again.';

        } catch (\Exception $e) {
            Log::error('GPT-4o generation error: ' . $e->getMessage());
            return 'I encountered an error while processing your question. Please try again.';
        }
    }
}
