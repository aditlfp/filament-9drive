<?php

namespace App\Infrastructure\Queue;

use App\Domain\Contracts\QueueServiceInterface;
use App\Infrastructure\Context\WorkspaceContext;
use Illuminate\Queue\SerializesModels;

/**
 * Base for jobs that must run within workspace context.
 * Restores workspace from ID stored when job was dispatched.
 */
abstract class WorkspaceAwareJob
{
    use SerializesModels;

    public int $workspaceId;

    public function __construct()
    {
        $this->workspaceId = WorkspaceContext::get()->id;
    }

    /**
     * Called by the queue worker before handle().
     */
    public function middleware(): array
    {
        return [new RestoresWorkspaceContext];
    }
}
