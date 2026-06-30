<?php

namespace App\Domain\Contracts;

use App\Domain\Entities\FileEntity;
use App\Domain\Entities\FolderEntity;
use App\Domain\ValueObjects\ProviderQuota;
use App\Domain\ValueObjects\UploadOptions;
use Psr\Http\Message\StreamInterface;

interface StorageProviderInterface
{
    /**
     * Authenticate with the provider.
     */
    public function authenticate(array $credentials): bool;

    /**
     * Refresh authentication token.
     */
    public function refreshToken(): bool;

    /**
     * Check if the connection is healthy.
     */
    public function healthCheck(): bool;

    /**
     * List files and folders in a directory.
     *
     * @return array{files: FileEntity[], folders: FolderEntity[]}
     */
    public function listDirectory(?string $providerFolderId = null): array;

    /**
     * Create a folder.
     */
    public function createFolder(string $name, ?string $parentProviderId = null): FolderEntity;

    /**
     * Upload a file.
     */
    public function upload(
        string $localPath,
        string $name,
        UploadOptions $options
    ): FileEntity;

    /**
     * Start a multipart upload session.
     */
    public function initiateMultipartUpload(string $name, UploadOptions $options): string;

    /**
     * Upload a chunk in multipart upload.
     */
    public function uploadChunk(string $uploadId, int $partNumber, string $data): string;

    /**
     * Complete multipart upload.
     */
    public function completeMultipartUpload(string $uploadId, array $parts): FileEntity;

    /**
     * Download a file as stream.
     */
    public function download(string $providerFileId): StreamInterface;

    /**
     * Delete a file.
     */
    public function deleteFile(string $providerFileId): bool;

    /**
     * Delete a folder.
     */
    public function deleteFolder(string $providerFolderId): bool;

    /**
     * Move a file to another folder.
     */
    public function moveFile(string $providerFileId, string $targetProviderFolderId): bool;

    /**
     * Copy a file.
     */
    public function copyFile(string $providerFileId, string $targetProviderFolderId): FileEntity;

    /**
     * Rename a file.
     */
    public function renameFile(string $providerFileId, string $newName): bool;

    /**
     * Rename a folder.
     */
    public function renameFolder(string $providerFolderId, string $newName): bool;

    /**
     * Search files by query.
     *
     * @return FileEntity[]
     */
    public function search(string $query, ?string $providerFolderId = null): array;

    /**
     * Get file metadata.
     */
    public function getFileMetadata(string $providerFileId): FileEntity;

    /**
     * Get folder metadata.
     */
    public function getFolderMetadata(string $providerFolderId): FolderEntity;

    /**
     * Generate a shareable link.
     */
    public function generateShareLink(string $providerFileId, array $options = []): string;

    /**
     * Revoke a share link.
     */
    public function revokeShareLink(string $providerFileId): bool;

    /**
     * Get storage quota information.
     */
    public function getQuota(): ProviderQuota;

    /**
     * Get provider identifier.
     */
    public function getProviderName(): string;

    /**
     * Get account identifier.
     */
    public function getAccountIdentifier(): string;
}
