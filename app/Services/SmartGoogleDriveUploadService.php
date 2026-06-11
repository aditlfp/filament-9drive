<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use App\Models\File;
use App\Models\Folder;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmartGoogleDriveUploadService
{
    public function __construct(
        protected GoogleDriveClientFactory $clientFactory,
        protected GoogleDriveQuotaService $quotaService,
    ) {}

    public function uploadUploadedFile(UploadedFile $uploadedFile, Folder $folder): File
    {
        return $this->uploadPath(
            path: $uploadedFile->getRealPath(),
            folder: $folder,
            name: $uploadedFile->getClientOriginalName(),
            size: $uploadedFile->getSize(),
            mimeType: $uploadedFile->getMimeType(),
        );
    }

    public function uploadPath(string $path, Folder $folder, string $name, int $size, ?string $mimeType): File
    {
        if (! is_file($path)) {
            throw new RuntimeException('The selected file could not be found.');
        }

        $account = $this->selectBestAccount($folder->user_id, $size);
        $drive = $this->clientFactory->make($account);

        $uploaded = $drive->files->create(
            new DriveFile([
                'name' => $name,
            ]),
            [
                'data' => file_get_contents($path),
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,size,mimeType',
            ],
        );

        return DB::transaction(function () use ($folder, $account, $uploaded, $name, $size, $mimeType): File {
            $file = File::query()->create([
                'folder_id' => $folder->id,
                'storage_account_id' => $account->id,
                'provider_file_id' => $uploaded->getId(),
                'name' => $uploaded->getName() ?: $name,
                'size' => $uploaded->getSize() ? (int) $uploaded->getSize() : $size,
                'mime_type' => $uploaded->getMimeType() ?: $mimeType,
            ]);

            $this->quotaService->refresh($account);

            return $file;
        });
    }

    protected function selectBestAccount(int $userId, int $size): ConnectedAccount
    {
        $accounts = $this->quotaService->refreshAllForUser($userId);

        if ($accounts->isEmpty()) {
            throw new RuntimeException('Connect at least one active Google Drive account before uploading.');
        }

        $knownQuotaAccounts = $accounts
            ->filter(fn (ConnectedAccount $account): bool => $account->quota_available !== null);

        $pool = $knownQuotaAccounts->isNotEmpty()
            ? $knownQuotaAccounts->filter(fn (ConnectedAccount $account): bool => $account->quota_available >= $size)
            : $accounts;

        if ($pool->isEmpty()) {
            $accounts = $this->quotaService->refreshAllForUser($userId, staleOnly: false);

            $pool = $accounts
                ->filter(fn (ConnectedAccount $account): bool => $account->quota_available === null || $account->quota_available >= $size);

            if ($pool->isEmpty()) {
                throw new RuntimeException('No connected Google Drive account has enough free quota for this file.');
            }
        }

        return $pool
            ->sortByDesc(fn (ConnectedAccount $account): int => $account->quota_available ?? PHP_INT_MAX)
            ->first();
    }
}
