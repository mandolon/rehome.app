<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function generateEmbedding(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'input' => $text,
                'model' => 'text-embedding-ada-002',
            ]);

            if ($response->successful()) {
                return $response->json('data.0.embedding');
            }

            Log::error('OpenAI embedding API error: ' . $response->body());
            throw new \Exception('Failed to generate embedding');

        } catch (\Exception $e) {
            Log::error('Embedding service error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generateEmbeddings(array $texts): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'input' => $texts,
                'model' => 'text-embedding-ada-002',
            ]);

            if ($response->successful()) {
                return collect($response->json('data'))->pluck('embedding')->toArray();
            }

            Log::error('OpenAI embedding API error: ' . $response->body());
            throw new \Exception('Failed to generate embeddings');

        } catch (\Exception $e) {
            Log::error('Embedding service error: ' . $e->getMessage());
            throw $e;
        }
    }
}
