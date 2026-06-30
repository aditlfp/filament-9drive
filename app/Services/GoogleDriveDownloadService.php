<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GoogleDriveDownloadService
{
    public function __construct(
        private readonly GoogleDriveClientFactory $driveFactory,
    ) {}

    public function streamFile(File $file): StreamedResponse
    {
        $stream = $this->mediaStream($file);
        $filename = $this->sanitizeFilename($file->name);

        return response()->stream(function () use ($stream): void {
            while (! $stream->eof()) {
                echo $stream->read(1024 * 64);
                flush();
            }
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Length' => (string) $file->size,
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param Collection<int, File> $files
     */
    public function streamZip(Collection $files, ?string $name = null): StreamedResponse
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The Zip PHP extension is required for bulk downloads.');
        }

        $zipPath = storage_path('app/private/my-files-download-' . Str::uuid() . '.zip');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create download archive.');
        }

        $usedNames = [];
        $tempPaths = [];

        try {
            foreach ($files as $file) {
                $tempPath = storage_path('app/private/my-files-download-entry-' . Str::uuid());
                $target = fopen($tempPath, 'wb');

                if ($target === false) {
                    throw new RuntimeException('Could not prepare download archive.');
                }

                $stream = $this->mediaStream($file);

                try {
                    while (! $stream->eof()) {
                        fwrite($target, $stream->read(1024 * 64));
                    }
                } finally {
                    fclose($target);
                }

                $tempPaths[] = $tempPath;
                $zip->addFile($tempPath, $this->uniqueFilename($this->sanitizeFilename($file->name), $usedNames));
            }
        } finally {
            $zip->close();

            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
        }

        $zipName = $this->sanitizeFilename($name ?: 'my-files-' . now()->format('Y-m-d-His') . '.zip');

        return response()->streamDownload(function () use ($zipPath): void {
            $handle = fopen($zipPath, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                while (! feof($handle)) {
                    echo fread($handle, 1024 * 64);
                    flush();
                }
            } finally {
                fclose($handle);
                @unlink($zipPath);
            }
        }, $zipName, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) filesize($zipPath),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function mediaStream(File $file)
    {
        $drive = $this->driveFactory->make($file->storageAccount);

        return $drive->files->get($file->provider_file_id, [
            'alt' => 'media',
        ])->getBody();
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filename));
        $filename = preg_replace('/[^\w\-.\s]/u', '_', $filename) ?: 'download';
        $filename = trim($filename, " .\t\n\r\0\x0B");

        if ($filename === '') {
            $filename = 'download';
        }

        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $maxNameLength = max(1, 254 - strlen($extension));
            $filename = substr($name, 0, $maxNameLength) . ($extension ? ".{$extension}" : '');
        }

        return $filename;
    }

    /**
     * @param array<string, int> $usedNames
     */
    private function uniqueFilename(string $filename, array &$usedNames): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $key = strtolower($filename);

        if (! isset($usedNames[$key])) {
            $usedNames[$key] = 1;

            return $filename;
        }

        $usedNames[$key]++;
        $suffix = ' (' . $usedNames[$key] . ')';

        return $base . $suffix . ($extension ? ".{$extension}" : '');
    }
}
