<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\GoogleDriveDownloadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function __construct(
        private readonly GoogleDriveDownloadService $downloadService,
    ) {}

    public function show(File $file): StreamedResponse
    {
        $this->authorizeFile($file);

        return $this->downloadService->streamFile($file->loadMissing('storageAccount'));
    }

    public function bulk(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'file_ids' => ['required', 'array', 'min:1', 'max:100'],
            'file_ids.*' => ['integer', 'distinct', 'exists:files,id'],
        ]);

        $ids = array_map('intval', $data['file_ids']);

        $files = File::query()
            ->with('storageAccount')
            ->ownedBy(auth()->id())
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (File $file): int => array_search($file->id, $ids, true))
            ->values();

        abort_unless($files->count() === count($ids), 403);

        return $this->downloadService->streamZip($files);
    }

    private function authorizeFile(File $file): void
    {
        $file->loadMissing('storageAccount');

        abort_unless($file->storageAccount->user_id === auth()->id(), 403);
        abort_unless(File::query()->ownedBy(auth()->id())->whereKey($file->id)->exists(), 403);
    }
}
