<?php

namespace App\Infrastructure\Providers;

use App\Domain\Contracts\StorageProviderRegistryInterface;
use App\Domain\Enums\StorageProviderType;
use App\Domain\ValueObjects\ProviderCapabilities;
use RuntimeException;

/**
 * Central registry for provider metadata and capabilities.
 */
class StorageProviderRegistry implements StorageProviderRegistryInterface
{
    /** @var array<StorageProviderType, class-string> */
    private array $providers = [];

    /** @var array<StorageProviderType, ProviderCapabilities> */
    private array $capabilities = [];

    /** @var array<StorageProviderType, string> */
    private array $names = [];

    public function register(
        StorageProviderType $type,
        string $providerClass,
        ?ProviderCapabilities $capabilities = null,
        ?string $name = null,
    ): void {
        $this->providers[$type->value] = $providerClass;
        $this->capabilities[$type->value] = $capabilities ?? $this->defaultCapabilities();
        $this->names[$type->value] = $name ?? $this->defaultName($type);
    }

    public function available(): array
    {
        return array_map(
            fn (string $value) => StorageProviderType::from($value),
            array_keys($this->providers)
        );
    }

    public function has(StorageProviderType $type): bool
    {
        return isset($this->providers[$type->value]);
    }

    public function name(StorageProviderType $type): string
    {
        return $this->names[$type->value] ?? throw new RuntimeException("Provider {$type->value} not registered.");
    }

    public function capabilities(StorageProviderType $type): ProviderCapabilities
    {
        return $this->capabilities[$type->value] ?? throw new RuntimeException("Provider {$type->value} not registered.");
    }

    public function providerClass(StorageProviderType $type): string
    {
        return $this->providers[$type->value] ?? throw new RuntimeException("Provider {$type->value} not registered.");
    }

    private function defaultCapabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities();
    }

    private function defaultName(StorageProviderType $type): string
    {
        return match ($type) {
            StorageProviderType::GOOGLE_DRIVE => 'Google Drive',
            StorageProviderType::AMAZON_S3 => 'Amazon S3',
            StorageProviderType::CLOUDFLARE_R2 => 'Cloudflare R2',
            StorageProviderType::MINIO => 'MinIO',
            StorageProviderType::DROPBOX => 'Dropbox',
            StorageProviderType::ONEDRIVE => 'Microsoft OneDrive',
            StorageProviderType::BACKBLAZE_B2 => 'Backblaze B2',
            StorageProviderType::WEBDAV => 'WebDAV',
            StorageProviderType::LOCAL => 'Local Storage',
        };
    }
}
