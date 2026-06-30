<?php

namespace App\Domain\Events;

class FolderCreated extends DomainEvent
{
    public function __construct(
        public readonly int $folderId,
        public readonly string $folderName,
        public readonly int $workspaceId,
        public readonly int $createdBy,
    ) {
        parent::__construct();
    }
}
