<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Document $document
    ) {}

    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            Log::info('Processing document', ['document_id' => $this->document->id]);
            
            $this->document->update(['status' => 'processing']);

            // Read file content
            $content = Storage::get($this->document->storage_path);
            
            if (!$content) {
                throw new \Exception('Could not read document content');
            }

            // Extract text based on mime type
            $text = $this->extractText($content, $this->document->mime_type);

            // Split into chunks (simplified chunking - 1000 chars per chunk)
            $chunks = $this->createChunks($text);

            // Generate embeddings for all chunks
            $embeddings = $embeddingService->generateEmbeddings($chunks);

            // Save chunks with embeddings
            foreach ($chunks as $index => $chunkText) {
                DocumentChunk::create([
                    'account_id' => $this->document->account_id,
                    'document_id' => $this->document->id,
                    'content' => $chunkText,
                    'embedding' => $embeddings[$index] ?? null,
                    'chunk_index' => $index,
                    'token_count' => $this->estimateTokens($chunkText),
                    'metadata' => [
                        'processed_at' => now()->toISOString(),
                        'chunk_size' => strlen($chunkText),
                    ],
                ]);
            }

            $this->document->update([
                'status' => 'completed',
                'metadata' => array_merge($this->document->metadata ?? [], [
                    'processed_at' => now()->toISOString(),
                    'chunk_count' => count($chunks),
                    'total_tokens' => array_sum(array_map([$this, 'estimateTokens'], $chunks)),
                ]),
            ]);

            Log::info('Document processed successfully', [
                'document_id' => $this->document->id,
                'chunks_created' => count($chunks),
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            $this->document->update([
                'status' => 'failed',
                'metadata' => array_merge($this->document->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString(),
                ]),
            ]);

            throw $e;
        }
    }

    private function extractText(string $content, string $mimeType): string
    {
        // Simplified text extraction - in production, use proper libraries
        switch ($mimeType) {
            case 'text/plain':
            case 'text/markdown':
                return $content;
            
            case 'application/pdf':
                // In production, use a PDF parsing library like Smalot\PdfParser
                return "PDF content extraction would go here";
            
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                // In production, use PhpOffice\PhpWord
                return "DOCX content extraction would go here";
            
            default:
                return $content;
        }
    }

    private function createChunks(string $text, int $maxChunkSize = 1000): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            if (strlen($currentChunk . $sentence) > $maxChunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ' ' . $sentence;
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return array_filter($chunks); // Remove empty chunks
    }

    private function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters for English text
        return (int) ceil(strlen($text) / 4);
    }
}
