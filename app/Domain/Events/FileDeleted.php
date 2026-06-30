<?php

namespace App\Domain\Events;

use App\Models\VirtualFile;

class FileDeleted extends DomainEvent
{
    public function __construct(
        public readonly int $fileId,
        public readonly string $fileName,
        public readonly int $workspaceId,
        public readonly int $deletedBy,
    ) {
        parent::__construct();
    }
}
