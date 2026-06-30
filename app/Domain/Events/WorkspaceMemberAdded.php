<?php

namespace App\Domain\Events;

class WorkspaceMemberAdded extends DomainEvent
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $userId,
        public readonly string $role,
        public readonly int $addedBy,
    ) {
        parent::__construct();
    }
}
