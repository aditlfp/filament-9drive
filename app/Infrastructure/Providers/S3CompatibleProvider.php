<?php

namespace App\Infrastructure\Providers;

use App\Domain\Contracts\StorageProviderInterface;
use App\Domain\Entities\FileEntity;
use App\Domain\Entities\FolderEntity;
use App\Domain\ValueObjects\ProviderQuota;
use App\Domain\ValueObjects\UploadOptions;
use App\Models\ConnectedAccount;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * S3-compatible provider: Amazon S3, Cloudflare R2, MinIO, Wasabi, Backblaze B2.
 * Differentiated by credentials.endpoint + credentials.region on ConnectedAccount.
 *
 * credentials JSON shape:
 * {
 *   "key":    "ACCESS_KEY_ID",
 *   "secret": "SECRET_ACCESS_KEY",
 *   "region": "us-east-1",
 *   "bucket": "my-bucket",
 *   "endpoint": "https://...",   // null → AWS default
 *   "use_path_style": true       // required for MinIO; false for S3/R2
 * }
 */
class S3CompatibleProvider implements StorageProviderInterface
{
    private S3Client $client;
    private string $bucket;

    public function __construct(private readonly ConnectedAccount $account)
    {
        $creds = $account->credentials;

        $config = [
            'version'     => 'latest',
            'region'      => $creds['region'] ?? 'us-east-1',
            'credentials' => [
                'key'    => $creds['key'],
                'secret' => $creds['secret'],
            ],
            'use_path_style_endpoint' => (bool) ($creds['use_path_style'] ?? false),
        ];

        if (! empty($creds['endpoint'])) {
            $config['endpoint'] = $creds['endpoint'];
        }

        $this->client = new S3Client($config);
        $this->bucket = $creds['bucket'];
    }

    // ── Auth ──────────────────────────────────────────────────────────────

    public function authenticate(array $credentials): bool
    {
        try {
            $this->client->headBucket(['Bucket' => $this->bucket]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function refreshToken(): bool
    {
        // S3 uses long-lived key/secret; no refresh needed.
        return true;
    }

    public function healthCheck(): bool
    {
        return $this->authenticate([]);
    }

    // ── Files ─────────────────────────────────────────────────────────────

    public function upload(string $localPath, string $name, UploadOptions $options): FileEntity
    {
        $key = $this->buildKey($options->providerFolderId, $name);

        $result = $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'SourceFile'  => $localPath,
            'ContentType' => $options->mimeType ?? 'application/octet-stream',
        ]);

        return new FileEntity(
            providerFileId: $key,
            name: $name,
            mimeType: $options->mimeType ?? 'application/octet-stream',
            size: filesize($localPath),
            providerPath: $key,
        );
    }

    public function initiateMultipartUpload(string $name, UploadOptions $options): string
    {
        $key = $this->buildKey($options->providerFolderId, $name);

        $result = $this->client->createMultipartUpload([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'ContentType' => $options->mimeType ?? 'application/octet-stream',
        ]);

        // Return composite ID: key|uploadId
        return $key . '|' . $result['UploadId'];
    }

    public function uploadChunk(string $uploadId, int $partNumber, string $data): string
    {
        [$key, $awsUploadId] = $this->parseUploadId($uploadId);

        $result = $this->client->uploadPart([
            'Bucket'     => $this->bucket,
            'Key'        => $key,
            'UploadId'   => $awsUploadId,
            'PartNumber' => $partNumber,
            'Body'       => $data,
        ]);

        return $result['ETag'];
    }

    public function completeMultipartUpload(string $uploadId, array $parts): FileEntity
    {
        [$key, $awsUploadId] = $this->parseUploadId($uploadId);

        $this->client->completeMultipartUpload([
            'Bucket'          => $this->bucket,
            'Key'             => $key,
            'UploadId'        => $awsUploadId,
            'MultipartUpload' => [
                'Parts' => array_map(fn ($p, $i) => [
                    'PartNumber' => $i + 1,
                    'ETag'       => $p,
                ], $parts, array_keys($parts)),
            ],
        ]);

        $meta = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $key]);

        return new FileEntity(
            providerFileId: $key,
            name: basename($key),
            mimeType: $meta['ContentType'] ?? 'application/octet-stream',
            size: $meta['ContentLength'] ?? 0,
            providerPath: $key,
        );
    }

    public function download(string $providerFileId): StreamInterface
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $providerFileId,
        ]);

        return $result['Body'];
    }

    public function deleteFile(string $providerFileId): bool
    {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $providerFileId]);
        return true;
    }

    public function moveFile(string $providerFileId, string $targetProviderFolderId): bool
    {
        $newKey = $this->buildKey($targetProviderFolderId, basename($providerFileId));
        $this->copyFile($providerFileId, $targetProviderFolderId);
        $this->deleteFile($providerFileId);
        return true;
    }

    public function copyFile(string $providerFileId, string $targetProviderFolderId): FileEntity
    {
        $newKey = $this->buildKey($targetProviderFolderId, basename($providerFileId));

        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'CopySource' => "{$this->bucket}/{$providerFileId}",
            'Key'        => $newKey,
        ]);

        $meta = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $newKey]);

        return new FileEntity(
            providerFileId: $newKey,
            name: basename($newKey),
            mimeType: $meta['ContentType'] ?? 'application/octet-stream',
            size: $meta['ContentLength'] ?? 0,
            providerPath: $newKey,
        );
    }

    public function renameFile(string $providerFileId, string $newName): bool
    {
        $newKey = $this->buildKey(dirname($providerFileId), $newName);
        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'CopySource' => "{$this->bucket}/{$providerFileId}",
            'Key'        => $newKey,
        ]);
        $this->deleteFile($providerFileId);
        return true;
    }

    public function getFileMetadata(string $providerFileId): FileEntity
    {
        $meta = $this->client->headObject(['Bucket' => $this->bucket, 'Key' => $providerFileId]);

        return new FileEntity(
            providerFileId: $providerFileId,
            name: basename($providerFileId),
            mimeType: $meta['ContentType'] ?? 'application/octet-stream',
            size: $meta['ContentLength'] ?? 0,
            providerPath: $providerFileId,
        );
    }

    // ── Folders ───────────────────────────────────────────────────────────

    public function listDirectory(?string $providerFolderId = null): array
    {
        $prefix = $providerFolderId ? rtrim($providerFolderId, '/') . '/' : '';

        $result = $this->client->listObjectsV2([
            'Bucket'    => $this->bucket,
            'Prefix'    => $prefix,
            'Delimiter' => '/',
        ]);

        $files = collect($result['Contents'] ?? [])
            ->reject(fn ($obj) => $obj['Key'] === $prefix) // skip folder itself
            ->map(fn ($obj) => new FileEntity(
                providerFileId: $obj['Key'],
                name: basename($obj['Key']),
                mimeType: 'application/octet-stream',
                size: $obj['Size'],
                providerPath: $obj['Key'],
            ))->values()->all();

        $folders = collect($result['CommonPrefixes'] ?? [])
            ->map(fn ($p) => new FolderEntity(
                providerFolderId: $p['Prefix'],
                name: basename(rtrim($p['Prefix'], '/')),
                providerPath: $p['Prefix'],
            ))->values()->all();

        return ['files' => $files, 'folders' => $folders];
    }

    public function createFolder(string $name, ?string $parentProviderId = null): FolderEntity
    {
        $key = $this->buildKey($parentProviderId, $name) . '/';

        // S3 folders are virtual — create a zero-byte object with trailing slash.
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => '',
        ]);

        return new FolderEntity(
            providerFolderId: $key,
            name: $name,
            providerPath: $key,
        );
    }

    public function deleteFolder(string $providerFolderId): bool
    {
        $prefix = rtrim($providerFolderId, '/') . '/';

        // List all objects under prefix and batch-delete
        $paginator = $this->client->getPaginator('ListObjectsV2', [
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);

        foreach ($paginator as $page) {
            $objects = array_map(fn ($obj) => ['Key' => $obj['Key']], $page['Contents'] ?? []);
            if (! empty($objects)) {
                $this->client->deleteObjects([
                    'Bucket' => $this->bucket,
                    'Delete' => ['Objects' => $objects],
                ]);
            }
        }

        return true;
    }

    public function renameFolder(string $providerFolderId, string $newName): bool
    {
        // S3 has no rename — copy all objects to new prefix, delete old.
        $oldPrefix = rtrim($providerFolderId, '/') . '/';
        $newPrefix = dirname(rtrim($providerFolderId, '/')) . '/' . $newName . '/';

        $paginator = $this->client->getPaginator('ListObjectsV2', [
            'Bucket' => $this->bucket,
            'Prefix' => $oldPrefix,
        ]);

        foreach ($paginator as $page) {
            foreach ($page['Contents'] ?? [] as $obj) {
                $newKey = $newPrefix . substr($obj['Key'], strlen($oldPrefix));
                $this->client->copyObject([
                    'Bucket'     => $this->bucket,
                    'CopySource' => "{$this->bucket}/{$obj['Key']}",
                    'Key'        => $newKey,
                ]);
                $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $obj['Key']]);
            }
        }

        return true;
    }

    public function getFolderMetadata(string $providerFolderId): FolderEntity
    {
        return new FolderEntity(
            providerFolderId: $providerFolderId,
            name: basename(rtrim($providerFolderId, '/')),
            providerPath: $providerFolderId,
        );
    }

    // ── Share ─────────────────────────────────────────────────────────────

    public function generateShareLink(string $providerFileId, array $options = []): string
    {
        $expiry = $options['expiry'] ?? '+1 hour';

        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $providerFileId,
        ]);

        return (string) $this->client->createPresignedRequest($cmd, $expiry)->getUri();
    }

    public function revokeShareLink(string $providerFileId): bool
    {
        // S3 presigned URLs can't be revoked; only bucket policy changes do.
        // ponytail: implement via ACL or short TTL strategy
        return false;
    }

    // ── Search ────────────────────────────────────────────────────────────

    public function search(string $query, ?string $providerFolderId = null): array
    {
        $prefix = $providerFolderId ? rtrim($providerFolderId, '/') . '/' : '';

        // S3 has no full-text search — list and filter by key name.
        // ponytail: S3 Select or Athena for content search
        $results = [];
        $paginator = $this->client->getPaginator('ListObjectsV2', [
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);

        foreach ($paginator as $page) {
            foreach ($page['Contents'] ?? [] as $obj) {
                if (str_contains(strtolower(basename($obj['Key'])), strtolower($query))) {
                    $results[] = new FileEntity(
                        providerFileId: $obj['Key'],
                        name: basename($obj['Key']),
                        mimeType: 'application/octet-stream',
                        size: $obj['Size'],
                        providerPath: $obj['Key'],
                    );
                }
            }
        }

        return $results;
    }

    // ── Quota ─────────────────────────────────────────────────────────────

    public function getQuota(): ProviderQuota
    {
        // S3 has no quota — unlimited.
        // ponytail: use CloudWatch StorageMetrics for used bytes per bucket
        return new ProviderQuota(total: null, used: null);
    }

    // ── Identity ─────────────────────────────────────────────────────────

    public function getProviderName(): string
    {
        return $this->account->provider_type ?? 's3_compatible';
    }

    public function getAccountIdentifier(): string
    {
        return $this->account->account_name ?? $this->bucket;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function buildKey(?string $folder, string $name): string
    {
        if (empty($folder) || $folder === '/') {
            return $name;
        }

        return rtrim($folder, '/') . '/' . $name;
    }

    private function parseUploadId(string $compositeId): array
    {
        $parts = explode('|', $compositeId, 2);

        if (count($parts) !== 2) {
            throw new RuntimeException("Invalid S3 composite upload ID: {$compositeId}");
        }

        return $parts;
    }
}
