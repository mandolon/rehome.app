<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class FileApiController extends Controller
{
    public function show(Request $request, File $file)
    {
        Gate::authorize('view', $file);

        // Generate signed URL for secure file access
        $signedUrl = $this->generateSignedDownloadUrl($file);

        return response()->json([
            'data' => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'download_url' => $signedUrl,
                'metadata' => $file->metadata,
                'created_at' => $file->created_at,
            ],
        ]);
    }

    public function download(Request $request, File $file)
    {
        Gate::authorize('view', $file);

        // Verify the signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link');
        }

        $filePath = $file->storage_path;

        if (!Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        return Storage::download($filePath, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    private function generateSignedDownloadUrl(File $file): string
    {
        return URL::signedRoute(
            'api.files.download',
            ['file' => $file->id],
            now()->addHour() // URL expires in 1 hour
        );
    }
}
