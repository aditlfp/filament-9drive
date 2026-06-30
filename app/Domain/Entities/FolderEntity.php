<?php

namespace App\Domain\Entities;

readonly class FolderEntity
{
    public function __construct(
        public string $providerFolderId,
        public string $name,
        public ?string $parentProviderFolderId = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
