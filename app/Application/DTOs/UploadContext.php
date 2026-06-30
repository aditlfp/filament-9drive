<?php

namespace App\Application\DTOs;

final readonly class UploadContext
{
    public function __construct(
        public int $workspaceId,
        public int $folderId,
        public int $userId,
        public string $fileName,
        public int $fileSize,
        public string $mimeType,
    ) {}
}
