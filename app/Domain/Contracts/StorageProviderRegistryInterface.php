<?php

namespace App\Domain\Contracts;

use App\Domain\Enums\StorageProviderType;
use App\Domain\ValueObjects\ProviderCapabilities;

/**
 * Registry for discovering available storage providers.
 * Decouples provider enumeration from factory instantiation.
 */
interface StorageProviderRegistryInterface
{
    /**
     * @return StorageProviderType[]
     */
    public function available(): array;

    /**
     * Check if a provider type is registered.
     */
    public function has(StorageProviderType $type): bool;

    /**
     * Get human-readable name for a provider type.
     */
    public function name(StorageProviderType $type): string;

    /**
     * Get capabilities for a provider type.
     */
    public function capabilities(StorageProviderType $type): ProviderCapabilities;  // @phpstan-ignore-line
}
