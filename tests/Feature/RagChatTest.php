<?php

use App\Models\Account;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Project;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RagChatTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->account = Account::factory()->create();
        $this->user = User::factory()->create(['account_id' => $this->account->id]);
        $this->project = Project::factory()->create(['account_id' => $this->account->id]);
    }

    /** @test */
    public function it_successfully_answers_questions_with_citations()
    {
        // Create completed documents with chunks
        $document1 = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'zoning-requirements.pdf',
            'status' => 'completed',
        ]);

        $document2 = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'building-permits.pdf',
            'status' => 'completed',
        ]);

        // Create document chunks with embeddings
        $chunk1 = DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $document1->id,
            'content' => 'The zoning requirements for residential buildings require a minimum setback of 25 feet from the front property line.',
            'embedding' => array_fill(0, 1536, 0.8), // High similarity embedding
            'chunk_index' => 0,
            'token_count' => 25,
        ]);

        $chunk2 = DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $document2->id,
            'content' => 'Building permits must be obtained before construction begins. The permit fee is $500 for residential projects.',
            'embedding' => array_fill(0, 1536, 0.7), // Medium similarity embedding
            'chunk_index' => 0,
            'token_count' => 20,
        ]);

        // Mock the EmbeddingService
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->with('What are the zoning setback requirements?')
            ->andReturn(array_fill(0, 1536, 0.8)); // Match chunk1 for high similarity

        // Mock GPT-4o API response
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Based on the zoning requirements document, residential buildings require a minimum setback of 25 feet from the front property line.'
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Make the request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => 'What are the zoning setback requirements?',
            ]);

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'ok',
                'answer',
                'citations' => [
                    '*' => [
                        'doc_id',
                        'chunk_no',
                    ],
                ],
            ])
            ->assertJson([
                'ok' => true,
            ]);

        // Assert citations contain document references
        $responseData = $response->json();
        $this->assertNotEmpty($responseData['citations']);
        $this->assertEquals($document1->id, $responseData['citations'][0]['doc_id']);
        $this->assertEquals(0, $responseData['citations'][0]['chunk_no']);

        // Assert OpenAI API was called with correct parameters
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
                   $request['model'] === 'gpt-4o' &&
                   str_contains($request['messages'][1]['content'], 'zoning requirements') &&
                   $request['temperature'] === 0.3;
        });
    }

    /** @test */
    public function it_returns_insufficient_context_when_no_relevant_chunks_found()
    {
        // Create document with low similarity content
        $document = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'unrelated-content.pdf',
            'status' => 'completed',
        ]);

        DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $document->id,
            'content' => 'This document discusses gardening techniques and plant care.',
            'embedding' => array_fill(0, 1536, 0.1), // Very low similarity embedding
            'chunk_index' => 0,
            'token_count' => 10,
        ]);

        // Mock the EmbeddingService
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->with('What are the construction permit requirements?')
            ->andReturn(array_fill(0, 1536, 0.9)); // High values that won't match low embedding

        // Make the request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => 'What are the construction permit requirements?',
            ]);

        // Assert insufficient context response
        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Insufficient context available in project documents to answer your question.',
            ]);

        // Assert no OpenAI API calls were made
        Http::assertNothingSent();
    }

    /** @test */
    public function it_handles_embedding_service_errors()
    {
        // Mock the EmbeddingService to throw an exception
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->andThrow(new \Exception('OpenAI embedding API error'));

        // Make the request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => 'Test question',
            ]);

        // Assert error response
        $response->assertStatus(500)
            ->assertJson([
                'ok' => false,
                'error' => 'Failed to process question',
                'message' => 'An error occurred while processing your request. Please try again.',
            ]);
    }

    /** @test */
    public function it_handles_gpt_api_errors()
    {
        // Create document with chunks
        $document = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'status' => 'completed',
        ]);

        DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $document->id,
            'content' => 'Test content for error handling.',
            'embedding' => array_fill(0, 1536, 0.8),
            'chunk_index' => 0,
            'token_count' => 5,
        ]);

        // Mock the EmbeddingService
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.8));

        // Mock GPT-4o API error response
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded',
                    'type' => 'rate_limit_error',
                ]
            ], 429)
        ]);

        // Make the request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => 'Test question',
            ]);

        // Should still return 200 but with error message in answer
        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ]);

        $responseData = $response->json();
        $this->assertStringContainsString('error', $responseData['answer']);
    }

    /** @test */
    public function it_validates_message_input()
    {
        // Test missing message
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        // Test empty message
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        // Test message too long (>2000 chars)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_requires_project_authorization()
    {
        // Create another account and project
        $otherAccount = Account::factory()->create();
        $otherProject = Project::factory()->create(['account_id' => $otherAccount->id]);

        // Try to ask question about project user doesn't have access to
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$otherProject->id}/ask", [
                'message' => 'Test question',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_only_searches_completed_documents()
    {
        // Create documents with different statuses
        $completedDoc = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'completed.pdf',
            'status' => 'completed',
        ]);

        $pendingDoc = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'pending.pdf',
            'status' => 'pending',
        ]);

        $failedDoc = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'original_name' => 'failed.pdf',
            'status' => 'failed',
        ]);

        // Create chunks for all documents
        DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $completedDoc->id,
            'content' => 'Relevant content from completed document.',
            'embedding' => array_fill(0, 1536, 0.8),
            'chunk_index' => 0,
        ]);

        DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $pendingDoc->id,
            'content' => 'Content from pending document.',
            'embedding' => array_fill(0, 1536, 0.9), // Even higher similarity
            'chunk_index' => 0,
        ]);

        DocumentChunk::factory()->create([
            'account_id' => $this->account->id,
            'document_id' => $failedDoc->id,
            'content' => 'Content from failed document.',
            'embedding' => array_fill(0, 1536, 0.9),
            'chunk_index' => 0,
        ]);

        // Mock the EmbeddingService
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.8));

        // Mock GPT-4o API response
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test response based on completed document only.'
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Make the request
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/ask", [
                'message' => 'Test question',
            ]);

        $response->assertStatus(200);
        
        // Verify only completed document is in citations
        $responseData = $response->json();
        $this->assertCount(1, $responseData['citations']);
        $this->assertEquals($completedDoc->id, $responseData['citations'][0]['doc_id']);
    }
}
