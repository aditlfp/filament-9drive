<?php

namespace App\Domain\Events;

use App\Models\VirtualFile;

class FileUploaded extends DomainEvent
{
    public function __construct(
        public readonly VirtualFile $file,
        public readonly int $workspaceId,
        public readonly int $uploadedBy,
    ) {
        parent::__construct();
    }
}
