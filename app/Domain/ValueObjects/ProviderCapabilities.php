<?php

namespace App\Domain\ValueObjects;

/**
 * Describes what a storage provider can do.
 * Used for UI decisions and feature detection.
 */
final readonly class ProviderCapabilities
{
    public function __construct(
        public bool $supportsMultipartUpload = false,
        public bool $supportsSharing = false,
        public bool $supportsVersioning = false,
        public bool $supportsTrash = false,
        public bool $supportsSearch = true,
        public bool $supportsQuota = true,
        public bool $supportsThumbnail = false,
        public int $maxFileSize = 0,
        public int $maxChunkSize = 0,
        public array $supportedMimeTypes = [],
    ) {}

    public static function googleDrive(): self
    {
        return new self(
            supportsMultipartUpload: true,
            supportsSharing: true,
            supportsVersioning: true,
            supportsTrash: true,
            supportsSearch: true,
            supportsQuota: true,
            supportsThumbnail: true,
            maxFileSize: 5_120_000_000, // 5GB
        );
    }

    public static function s3Compatible(): self
    {
        return new self(
            supportsMultipartUpload: true,
            supportsSharing: false,
            supportsVersioning: true,
            supportsTrash: false,
            supportsSearch: false,
            supportsQuota: false,
            supportsThumbnail: false,
            maxFileSize: 5_000_000_000_000, // 5TB
            maxChunkSize: 100_000_000, // 100MB
        );
    }

    public static function dropbox(): self
    {
        return new self(
            supportsMultipartUpload: true,
            supportsSharing: true,
            supportsVersioning: true,
            supportsTrash: true,
            supportsSearch: true,
            supportsQuota: true,
            maxFileSize: 350_000_000_000, // 350GB
        );
    }

    public static function local(): self
    {
        return new self(
            supportsMultipartUpload: false,
            supportsSharing: false,
            supportsVersioning: false,
            supportsTrash: false,
            supportsSearch: false,
            supportsQuota: false,
            supportsThumbnail: false,
        );
    }
}
