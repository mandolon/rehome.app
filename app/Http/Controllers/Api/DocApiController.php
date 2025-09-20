<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Document;
use App\Jobs\IngestDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocApiController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Gate::authorize('uploadDocs', $project);

        $validated = $request->validate([
            'files' => 'required|array|max:5',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,md|max:10240', // 10MB max
        ]);

        $uploadedDocs = [];

        foreach ($validated['files'] as $file) {
            // Store file with organized path: account_id/project_id/filename
            $storagePath = sprintf(
                'documents/%d/%d/%s',
                $project->account_id,
                $project->id,
                uniqid() . '_' . $file->getClientOriginalName()
            );

            $path = $file->storeAs('', $storagePath, 'local');

            // Create document record
            $document = Document::create([
                'project_id' => $project->id,
                'account_id' => $project->account_id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'status' => 'pending',
                'metadata' => [
                    'uploaded_by' => $request->user()->id,
                    'uploaded_at' => now()->toISOString(),
                ],
            ]);

            // Queue for processing with IngestDocumentJob (chunking, embedding, etc.)
            $job = IngestDocumentJob::dispatch($project->id, $document->id, $path);

            $uploadedDocs[] = [
                'id' => $document->id,
                'name' => $document->original_name,
                'size' => $document->size,
                'status' => $document->status,
                'job_id' => $job->getJobId() ?? uniqid('job_'),
            ];
        }

        return response()->json([
            'ok' => true,
            'data' => $uploadedDocs,
            'message' => sprintf('%d document(s) uploaded and queued for processing', count($uploadedDocs)),
        ], 201);
    }
}
