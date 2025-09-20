<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class FileApiController extends Controller
{
    public function show(Request $request, File $file)
    {
        Gate::authorize('view', $file);

        // Validate storage path is within allowed directories
        if (!$this->isValidStoragePath($file->storage_path)) {
            return Api::error('INVALID_FILE_PATH', 'File path validation failed', 403);
        }

        // Generate signed URL for secure file access
        $signedUrl = $this->generateSignedDownloadUrl($file);

        return Api::ok([
            'id' => $file->id,
            'name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'download_url' => $signedUrl,
            'metadata' => $file->metadata,
            'created_at' => $file->created_at,
        ]);
    }

    public function download(Request $request, File $file)
    {
        Gate::authorize('view', $file);

        // Verify the signed URL
        if (!$request->hasValidSignature()) {
            return Api::error('INVALID_SIGNATURE', 'Invalid or expired download link', 403);
        }

        // Validate storage path
        if (!$this->isValidStoragePath($file->storage_path)) {
            return Api::error('INVALID_FILE_PATH', 'File path validation failed', 403);
        }

        $filePath = $file->storage_path;

        if (!Storage::disk('private')->exists($filePath)) {
            return Api::notFound('File not found on storage');
        }

        return Storage::disk('private')->download($filePath, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    private function generateSignedDownloadUrl(File $file): string
    {
        return URL::signedRoute(
            'api.files.download',
            ['file' => $file->id],
            now()->addMinutes(15) // URL expires in 15 minutes for security
        );
    }

    private function isValidStoragePath(string $path): bool
    {
        // Ensure path is within allowed directories
        $allowedPrefixes = [
            'account-',
            'project-',
            'documents/',
            'uploads/',
        ];

        // Check for directory traversal attempts
        if (str_contains($path, '..') || str_contains($path, '//')) {
            return false;
        }

        // Must start with one of the allowed prefixes
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
