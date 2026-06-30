<?php

namespace App\Domain\ValueObjects;

readonly class UploadOptions
{
    public function __construct(
        public ?string $providerFolderId = null,
        public ?string $mimeType = null,
        public ?array $metadata = null,
        public bool $overwrite = false,
    ) {}
}
