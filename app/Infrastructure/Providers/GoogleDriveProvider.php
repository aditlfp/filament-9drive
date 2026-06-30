<?php

namespace App\Infrastructure\Providers;

use App\Domain\Contracts\StorageProviderInterface;
use App\Domain\Entities\FileEntity;
use App\Domain\Entities\FolderEntity;
use App\Domain\ValueObjects\ProviderQuota;
use App\Domain\ValueObjects\UploadOptions;
use App\Models\ConnectedAccount;
use App\Services\GoogleDriveClientFactory;
use Psr\Http\Message\StreamInterface;

class GoogleDriveProvider implements StorageProviderInterface
{
    private \Google\Service\Drive $drive;

    public function __construct(
        private readonly ConnectedAccount $account,
        private readonly GoogleDriveClientFactory $clientFactory,
    ) {
        $client = $this->clientFactory->make($account);
        $this->drive = new \Google\Service\Drive($client);
    }

    public function authenticate(array $credentials): bool
    {
        try {
            $this->drive->about->get(['fields' => 'user']);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function refreshToken(): bool
    {
        try {
            $client = $this->clientFactory->make($this->account);
            $client->fetchAccessTokenWithRefreshToken($this->account->refresh_token);

            $this->account->update([
                'access_token' => $client->getAccessToken()['access_token'],
                'expires_at' => now()->addSeconds($client->getAccessToken()['expires_in'] ?? 3600),
            ]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function healthCheck(): bool
    {
        try {
            $this->drive->about->get(['fields' => 'user']);

            $this->account->update([
                'last_health_check_at' => now(),
                'health_status' => 'healthy',
            ]);

            return true;
        } catch (\Exception) {
            $this->account->update([
                'last_health_check_at' => now(),
                'health_status' => 'unhealthy',
            ]);

            return false;
        }
    }

    public function listDirectory(?string $providerFolderId = null): array
    {
        $query = $providerFolderId
            ? "'{$providerFolderId}' in parents and trashed = false"
            : "'root' in parents and trashed = false";

        $results = $this->drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, mimeType, size, parents, createdTime, modifiedTime)',
        ]);

        $files = [];
        $folders = [];

        foreach ($results->getFiles() as $item) {
            if ($item->mimeType === 'application/vnd.google-apps.folder') {
                $folders[] = new FolderEntity(
                    providerFolderId: $item->id,
                    name: $item->name,
                    parentProviderFolderId: $item->parents[0] ?? null,
                    createdAt: new \DateTime($item->createdTime),
                    updatedAt: new \DateTime($item->modifiedTime),
                );
            } else {
                $files[] = new FileEntity(
                    providerFileId: $item->id,
                    name: $item->name,
                    size: (int) $item->size,
                    mimeType: $item->mimeType,
                    providerFolderId: $item->parents[0] ?? null,
                    createdAt: new \DateTime($item->createdTime),
                    updatedAt: new \DateTime($item->modifiedTime),
                );
            }
        }

        return compact('files', 'folders');
    }

    public function createFolder(string $name, ?string $parentProviderId = null): FolderEntity
    {
        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parentProviderId ? [$parentProviderId] : ['root'],
        ]);

        $folder = $this->drive->files->create($fileMetadata, ['fields' => 'id, name, parents, createdTime, modifiedTime']);

        return new FolderEntity(
            providerFolderId: $folder->id,
            name: $folder->name,
            parentProviderFolderId: $folder->parents[0] ?? null,
            createdAt: new \DateTime($folder->createdTime),
            updatedAt: new \DateTime($folder->modifiedTime),
        );
    }

    public function upload(string $localPath, string $name, UploadOptions $options): FileEntity
    {
        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $name,
            'parents' => $options->providerFolderId ? [$options->providerFolderId] : ['root'],
        ]);

        $content = file_get_contents($localPath);
        $mimeType = $options->mimeType ?? mime_content_type($localPath) ?: 'application/octet-stream';

        $file = $this->drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, name, size, mimeType, parents, createdTime, modifiedTime',
        ]);

        return new FileEntity(
            providerFileId: $file->id,
            name: $file->name,
            size: (int) $file->size,
            mimeType: $file->mimeType,
            providerFolderId: $file->parents[0] ?? null,
            createdAt: new \DateTime($file->createdTime),
            updatedAt: new \DateTime($file->modifiedTime),
        );
    }

    public function initiateMultipartUpload(string $name, UploadOptions $options): string
    {
        // Google Drive uses resumable upload - return session URI
        throw new \RuntimeException('Use upload() method for Google Drive');
    }

    public function uploadChunk(string $uploadId, int $partNumber, string $data): string
    {
        throw new \RuntimeException('Use upload() method for Google Drive');
    }

    public function completeMultipartUpload(string $uploadId, array $parts): FileEntity
    {
        throw new \RuntimeException('Use upload() method for Google Drive');
    }

    public function download(string $providerFileId): StreamInterface
    {
        $response = $this->drive->files->get($providerFileId, ['alt' => 'media']);
        return $response->getBody();
    }

    public function deleteFile(string $providerFileId): bool
    {
        try {
            $this->drive->files->delete($providerFileId);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function deleteFolder(string $providerFolderId): bool
    {
        return $this->deleteFile($providerFolderId);
    }

    public function moveFile(string $providerFileId, string $targetProviderFolderId): bool
    {
        try {
            $file = $this->drive->files->get($providerFileId, ['fields' => 'parents']);
            $previousParents = implode(',', $file->parents);

            $this->drive->files->update($providerFileId, new \Google\Service\Drive\DriveFile(), [
                'addParents' => $targetProviderFolderId,
                'removeParents' => $previousParents,
                'fields' => 'id, parents',
            ]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function copyFile(string $providerFileId, string $targetProviderFolderId): FileEntity
    {
        $copy = new \Google\Service\Drive\DriveFile([
            'parents' => [$targetProviderFolderId],
        ]);

        $file = $this->drive->files->copy($providerFileId, $copy, [
            'fields' => 'id, name, size, mimeType, parents, createdTime, modifiedTime',
        ]);

        return new FileEntity(
            providerFileId: $file->id,
            name: $file->name,
            size: (int) $file->size,
            mimeType: $file->mimeType,
            providerFolderId: $file->parents[0] ?? null,
            createdAt: new \DateTime($file->createdTime),
            updatedAt: new \DateTime($file->modifiedTime),
        );
    }

    public function renameFile(string $providerFileId, string $newName): bool
    {
        try {
            $this->drive->files->update($providerFileId, new \Google\Service\Drive\DriveFile(['name' => $newName]));
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function renameFolder(string $providerFolderId, string $newName): bool
    {
        return $this->renameFile($providerFolderId, $newName);
    }

    public function search(string $query, ?string $providerFolderId = null): array
    {
        $q = "name contains '{$query}' and trashed = false";

        if ($providerFolderId) {
            $q .= " and '{$providerFolderId}' in parents";
        }

        $results = $this->drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id, name, size, mimeType, parents, createdTime, modifiedTime)',
        ]);

        $files = [];

        foreach ($results->getFiles() as $item) {
            if ($item->mimeType !== 'application/vnd.google-apps.folder') {
                $files[] = new FileEntity(
                    providerFileId: $item->id,
                    name: $item->name,
                    size: (int) $item->size,
                    mimeType: $item->mimeType,
                    providerFolderId: $item->parents[0] ?? null,
                    createdAt: new \DateTime($item->createdTime),
                    updatedAt: new \DateTime($item->modifiedTime),
                );
            }
        }

        return $files;
    }

    public function getFileMetadata(string $providerFileId): FileEntity
    {
        $file = $this->drive->files->get($providerFileId, [
            'fields' => 'id, name, size, mimeType, parents, createdTime, modifiedTime',
        ]);

        return new FileEntity(
            providerFileId: $file->id,
            name: $file->name,
            size: (int) $file->size,
            mimeType: $file->mimeType,
            providerFolderId: $file->parents[0] ?? null,
            createdAt: new \DateTime($file->createdTime),
            updatedAt: new \DateTime($file->modifiedTime),
        );
    }

    public function getFolderMetadata(string $providerFolderId): FolderEntity
    {
        $folder = $this->drive->files->get($providerFolderId, [
            'fields' => 'id, name, parents, createdTime, modifiedTime',
        ]);

        return new FolderEntity(
            providerFolderId: $folder->id,
            name: $folder->name,
            parentProviderFolderId: $folder->parents[0] ?? null,
            createdAt: new \DateTime($folder->createdTime),
            updatedAt: new \DateTime($folder->modifiedTime),
        );
    }

    public function generateShareLink(string $providerFileId, array $options = []): string
    {
        $permission = new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);

        $this->drive->permissions->create($providerFileId, $permission);

        $file = $this->drive->files->get($providerFileId, ['fields' => 'webViewLink']);

        return $file->webViewLink;
    }

    public function revokeShareLink(string $providerFileId): bool
    {
        try {
            $permissions = $this->drive->permissions->listPermissions($providerFileId);

            foreach ($permissions->getPermissions() as $permission) {
                if ($permission->type === 'anyone') {
                    $this->drive->permissions->delete($providerFileId, $permission->id);
                }
            }

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getQuota(): ProviderQuota
    {
        $about = $this->drive->about->get(['fields' => 'storageQuota']);
        $quota = $about->getStorageQuota();

        return new ProviderQuota(
            total: $quota->limit ? (int) $quota->limit : null,
            used: (int) $quota->usage,
            available: $quota->limit ? (int) ($quota->limit - $quota->usage) : null,
        );
    }

    public function getProviderName(): string
    {
        return 'google_drive';
    }

    public function getAccountIdentifier(): string
    {
        return $this->account->google_email;
    }
}
