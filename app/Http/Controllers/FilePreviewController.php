<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\GoogleDriveClientFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilePreviewController extends Controller
{
    public function __construct(
        private readonly GoogleDriveClientFactory $driveFactory
    ) {}

    public function image(Request $request, File $file): StreamedResponse
    {
        // Pastikan file ini milik user yang login
        abort_unless($file->storageAccount->user_id === auth()->id(), 403);

        // Pastikan ini memang file gambar
        abort_unless($this->isImage($file->mime_type), 422, 'Not an image file.');
        $driveService = $this->driveFactory->make($file->storageAccount);

        $response = $driveService->files->get($file->provider_file_id, [
            'alt' => 'media',
        ]);

        $stream = $response->getBody();

        return response()->stream(function () use ($stream) {
            while (!$stream->eof()) {
                echo $stream->read(1024 * 8); // 8KB chunks
                flush();
            }
        }, 200, [
            'Content-Type'        => $file->mime_type,
            'Cache-Control'       => 'private, max-age=3600',
            'Content-Disposition' => 'inline; filename="' . $file->name . '"',
        ]);
    }

    private function isImage(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ]);
    }
}
