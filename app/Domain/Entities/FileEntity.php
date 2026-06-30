<?php

namespace App\Domain\Entities;

readonly class FileEntity
{
    public function __construct(
        public string $providerFileId,
        public string $name,
        public int $size,
        public string $mimeType,
        public ?string $providerFolderId = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        public ?array $metadata = null,
    ) {}
}
