<?php

use App\Jobs\IngestDocumentJob;
use App\Models\Account;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Project;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocIngestionTest extends TestCase
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
        
        Storage::fake('local');
        Queue::fake();
    }

    /** @test */
    public function it_uploads_documents_and_dispatches_ingestion_job_with_job_id()
    {
        // Create a test file
        $file = UploadedFile::fake()->create('test-document.txt', 100, 'text/plain');
        
        // Act as authenticated user
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/docs", [
                'files' => [$file],
            ]);

        // Assert response structure
        $response->assertStatus(201)
            ->assertJsonStructure([
                'ok',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'size',
                        'status',
                        'job_id',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'ok' => true,
            ]);

        // Assert document was created
        $this->assertDatabaseHas('documents', [
            'project_id' => $this->project->id,
            'account_id' => $this->account->id,
            'original_name' => 'test-document.txt',
            'mime_type' => 'text/plain',
            'status' => 'pending',
        ]);

        // Assert file was stored
        $document = Document::where('original_name', 'test-document.txt')->first();
        Storage::disk('local')->assertExists($document->storage_path);

        // Assert IngestDocumentJob was dispatched
        Queue::assertPushed(IngestDocumentJob::class, function ($job) use ($document) {
            return $job->projectId === $this->project->id &&
                   $job->docId === $document->id &&
                   str_contains($job->path, 'test-document.txt');
        });
    }

    /** @test */
    public function it_processes_document_with_mocked_embedding_service()
    {
        // Fake the embedding service
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbeddings')
            ->once()
            ->andReturn([
                // Mock 1536-dimensional embeddings for chunks
                array_fill(0, 1536, 0.1),
                array_fill(0, 1536, 0.2),
            ]);

        // Create a document with test content
        $testContent = str_repeat('This is a test sentence for document ingestion. ', 100);
        Storage::put('test-path.txt', $testContent);
        
        $document = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => 'test-path.txt',
            'mime_type' => 'text/plain',
            'status' => 'pending',
        ]);

        // Execute the ingestion job
        $job = new IngestDocumentJob($this->project->id, $document->id, 'test-path.txt');
        $job->handle($mockEmbeddingService);

        // Assert document status was updated
        $document->refresh();
        $this->assertEquals('completed', $document->status);
        $this->assertArrayHasKey('processed_at', $document->metadata);
        $this->assertArrayHasKey('chunk_count', $document->metadata);
        $this->assertArrayHasKey('total_tokens', $document->metadata);
        $this->assertEquals(1536, $document->metadata['embedding_dimensions']);

        // Assert chunks were created
        $chunks = DocumentChunk::where('document_id', $document->id)->get();
        $this->assertGreaterThan(0, $chunks->count());

        foreach ($chunks as $index => $chunk) {
            // Assert chunk structure
            $this->assertEquals($this->account->id, $chunk->account_id);
            $this->assertEquals($document->id, $chunk->document_id);
            $this->assertNotEmpty($chunk->content);
            $this->assertIsArray($chunk->embedding);
            $this->assertCount(1536, $chunk->embedding);
            $this->assertEquals($index, $chunk->chunk_index);
            $this->assertGreaterThan(0, $chunk->token_count);
            $this->assertArrayHasKey('processed_at', $chunk->metadata);
            $this->assertArrayHasKey('embedding_model', $chunk->metadata);
            $this->assertEquals('text-embedding-3-small', $chunk->metadata['embedding_model']);
        }
    }

    /** @test */
    public function it_handles_job_failures_gracefully()
    {
        $mockEmbeddingService = $this->mock(EmbeddingService::class);
        $mockEmbeddingService->shouldReceive('generateEmbeddings')
            ->once()
            ->andThrow(new \Exception('OpenAI API error'));

        Storage::put('test-fail.txt', 'Test content');
        
        $document = Document::factory()->create([
            'account_id' => $this->account->id,
            'project_id' => $this->project->id,
            'storage_path' => 'test-fail.txt',
            'mime_type' => 'text/plain',
            'status' => 'pending',
        ]);

        // Execute the job and expect it to fail
        $job = new IngestDocumentJob($this->project->id, $document->id, 'test-fail.txt');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI API error');
        
        $job->handle($mockEmbeddingService);

        // Assert document status was updated to failed
        $document->refresh();
        $this->assertEquals('failed', $document->status);
        $this->assertArrayHasKey('error', $document->metadata);
        $this->assertArrayHasKey('failed_at', $document->metadata);
    }

    /** @test */
    public function it_validates_file_upload_requirements()
    {
        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('invalid.exe', 100, 'application/exe');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$this->project->id}/docs", [
                'files' => [$invalidFile],
            ]);

        $response->assertStatus(422);
    }
}
