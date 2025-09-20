<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    /**
     * Find the most relevant document chunks for a given embedding
     */
    public function findRelevantChunks(Project $project, array $embedding, int $limit = 12): Collection
    {
        // Get all chunks for completed documents in this project
        $chunks = DocumentChunk::whereHas('document', function ($query) use ($project) {
            $query->where('project_id', $project->id)
                  ->where('status', 'completed');
        })
        ->whereNotNull('embedding')
        ->with(['document:id,original_name'])
        ->get();

        if ($chunks->isEmpty()) {
            return collect();
        }

        // Calculate cosine similarity for each chunk
        $scoredChunks = $chunks->map(function (DocumentChunk $chunk) use ($embedding) {
            $similarity = $this->cosineSimilarity($embedding, $chunk->embedding);
            
            return [
                'chunk' => $chunk,
                'similarity' => $similarity,
                'doc_id' => $chunk->document_id,
                'chunk_no' => $chunk->chunk_index,
            ];
        });

        // Filter by relevance threshold and sort by similarity
        return $scoredChunks
            ->filter(fn($item) => $item['similarity'] > 0.6) // Relevance threshold
            ->sortByDesc('similarity')
            ->take($limit);
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || empty($a)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Build context string from relevant chunks
     */
    public function buildContext(Collection $relevantChunks): string
    {
        if ($relevantChunks->isEmpty()) {
            return '';
        }

        $contextParts = [];
        
        foreach ($relevantChunks as $item) {
            $chunk = $item['chunk'];
            $contextParts[] = sprintf(
                "Document: %s (Chunk %d)\n%s",
                $chunk->document->original_name,
                $chunk->chunk_index,
                $chunk->content
            );
        }

        return implode("\n\n---\n\n", $contextParts);
    }

    /**
     * Format citations for response
     */
    public function formatCitations(Collection $relevantChunks): array
    {
        return $relevantChunks->map(function ($item) {
            return [
                'doc_id' => $item['doc_id'],
                'chunk_no' => $item['chunk_no'],
            ];
        })->toArray();
    }
}
