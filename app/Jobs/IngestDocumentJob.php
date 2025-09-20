<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Project;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IngestDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $projectId,
        public readonly int $docId,
        public readonly string $path
    ) {}

    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            Log::info('Starting document ingestion', [
                'project_id' => $this->projectId,
                'doc_id' => $this->docId,
                'path' => $this->path,
            ]);

            // Find the project and document
            $project = Project::findOrFail($this->projectId);
            $document = Document::findOrFail($this->docId);

            // Update document status
            $document->update(['status' => 'processing']);

            // Read file content
            $content = Storage::get($this->path);
            
            if (!$content) {
                throw new \Exception('Could not read document content from path: ' . $this->path);
            }

            // Extract text based on mime type (stub implementation as specified)
            $text = $this->extractText($content, $document->mime_type);

            // Split into chunks (~900 tokens each)
            $chunks = $this->createChunks($text, 900);

            Log::info('Created chunks for document', [
                'document_id' => $this->docId,
                'chunk_count' => count($chunks),
            ]);

            // Generate embeddings for all chunks
            $chunkTexts = array_column($chunks, 'content');
            $embeddings = $embeddingService->generateEmbeddings($chunkTexts);

            // Upsert document chunks with embeddings (1536 dimensions)
            foreach ($chunks as $index => $chunk) {
                DocumentChunk::updateOrCreate(
                    [
                        'document_id' => $this->docId,
                        'chunk_index' => $index,
                    ],
                    [
                        'account_id' => $project->account_id,
                        'content' => $chunk['content'],
                        'embedding' => $embeddings[$index] ?? null,
                        'token_count' => $chunk['token_count'],
                        'metadata' => [
                            'processed_at' => now()->toISOString(),
                            'chunk_size' => strlen($chunk['content']),
                            'embedding_model' => 'text-embedding-3-small',
                        ],
                    ]
                );
            }

            // Update document with completion status
            $document->update([
                'status' => 'completed',
                'metadata' => array_merge($document->metadata ?? [], [
                    'processed_at' => now()->toISOString(),
                    'chunk_count' => count($chunks),
                    'total_tokens' => array_sum(array_column($chunks, 'token_count')),
                    'embedding_dimensions' => 1536,
                ]),
            ]);

            Log::info('Document ingestion completed successfully', [
                'document_id' => $this->docId,
                'chunks_created' => count($chunks),
                'total_tokens' => array_sum(array_column($chunks, 'token_count')),
            ]);

        } catch (\Exception $e) {
            Log::error('Document ingestion failed', [
                'project_id' => $this->projectId,
                'doc_id' => $this->docId,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);

            // Update document with failure status if it exists
            if (isset($document)) {
                $document->update([
                    'status' => 'failed',
                    'metadata' => array_merge($document->metadata ?? [], [
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toISOString(),
                    ]),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Extract text from file content (stub implementation as specified)
     */
    private function extractText(string $content, string $mimeType): string
    {
        // Stub implementation - in production use proper text extraction libraries
        switch ($mimeType) {
            case 'text/plain':
            case 'text/markdown':
                return $content;
            
            case 'application/pdf':
                // Stub: In production, use Smalot\PdfParser or similar
                return "Extracted PDF content: " . substr($content, 0, 1000) . "...";
            
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                // Stub: In production, use PhpOffice\PhpWord
                return "Extracted DOCX content: " . substr($content, 0, 1000) . "...";
            
            case 'application/json':
                return $content;
                
            default:
                // Return first 1000 characters as fallback
                return substr($content, 0, 1000);
        }
    }

    /**
     * Create text chunks of approximately targetTokens size
     */
    private function createChunks(string $text, int $targetTokens = 900): array
    {
        $chunks = [];
        
        // Split by sentences first
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            $testChunk = empty($currentChunk) ? $sentence : $currentChunk . ' ' . $sentence;
            $estimatedTokens = $this->estimateTokens($testChunk);
            
            if ($estimatedTokens > $targetTokens && !empty($currentChunk)) {
                // Save current chunk and start new one
                $chunks[] = [
                    'content' => trim($currentChunk),
                    'token_count' => $this->estimateTokens($currentChunk),
                ];
                $currentChunk = $sentence;
            } else {
                $currentChunk = $testChunk;
            }
        }
        
        // Add final chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'token_count' => $this->estimateTokens($currentChunk),
            ];
        }
        
        return array_filter($chunks, fn($chunk) => !empty(trim($chunk['content'])));
    }

    /**
     * Estimate token count for text
     * More accurate estimation: ~3.5 characters per token for English
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 3.5);
    }
}
