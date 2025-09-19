<?php

namespace App\Services;

use App\Models\Project;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagService
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {}

    public function askQuestion(Project $project, string $question, int $contextLimit = 3): array
    {
        // Generate embedding for the question
        $questionEmbedding = $this->embeddingService->generateEmbedding($question);

        // Find relevant document chunks using vector similarity
        $relevantChunks = $this->findRelevantChunks(
            $project, 
            $questionEmbedding, 
            $contextLimit
        );

        if (empty($relevantChunks)) {
            return [
                'answer' => 'I couldn\'t find relevant information in the project documents to answer your question.',
                'sources' => [],
                'confidence' => 0,
            ];
        }

        // Build context from relevant chunks
        $context = $this->buildContext($relevantChunks);

        // Generate answer using OpenAI
        $answer = $this->generateAnswer($question, $context);

        return [
            'answer' => $answer,
            'sources' => $this->formatSources($relevantChunks),
            'confidence' => $this->calculateConfidence($relevantChunks),
        ];
    }

    private function findRelevantChunks(Project $project, array $questionEmbedding, int $limit): array
    {
        // This is a simplified implementation - in production, you'd use a vector database
        $chunks = DocumentChunk::whereHas('document', function ($query) use ($project) {
            $query->where('project_id', $project->id)
                  ->where('status', 'completed');
        })
        ->whereNotNull('embedding')
        ->get();

        $scoredChunks = [];

        foreach ($chunks as $chunk) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $chunk->embedding);
            if ($similarity > 0.7) { // Relevance threshold
                $scoredChunks[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity and take top results
        usort($scoredChunks, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($scoredChunks, 0, $limit);
    }

    private function buildContext(array $relevantChunks): string
    {
        $contextParts = [];
        
        foreach ($relevantChunks as $item) {
            $chunk = $item['chunk'];
            $contextParts[] = "Document: {$chunk->document->original_name}\n{$chunk->content}\n";
        }

        return implode("\n---\n", $contextParts);
    }

    private function generateAnswer(string $question, string $context): string
    {
        $systemPrompt = "You are a helpful assistant that answers questions based on the provided document context. " .
                       "Use only the information from the context to answer questions. " .
                       "If the context doesn't contain enough information, say so clearly.";

        $prompt = "Context:\n{$context}\n\nQuestion: {$question}\n\nAnswer:";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error('OpenAI API error: ' . $response->body());
            return 'Sorry, I encountered an error while generating the response.';

        } catch (\Exception $e) {
            Log::error('RAG service error: ' . $e->getMessage());
            return 'Sorry, I encountered an error while processing your question.';
        }
    }

    private function formatSources(array $relevantChunks): array
    {
        $sources = [];
        
        foreach ($relevantChunks as $item) {
            $chunk = $item['chunk'];
            $sources[] = [
                'document_name' => $chunk->document->original_name,
                'document_id' => $chunk->document_id,
                'chunk_index' => $chunk->chunk_index,
                'similarity' => round($item['similarity'], 3),
                'snippet' => substr($chunk->content, 0, 150) . '...',
            ];
        }

        return $sources;
    }

    private function calculateConfidence(array $relevantChunks): float
    {
        if (empty($relevantChunks)) {
            return 0;
        }

        $avgSimilarity = collect($relevantChunks)->avg('similarity');
        return round($avgSimilarity * 100, 1);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / ($normA * $normB);
    }
}
