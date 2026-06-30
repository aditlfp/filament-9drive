<?php

namespace App\Infrastructure\Queue;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\Workspace;
use Closure;

class RestoresWorkspaceContext
{
    public function handle(WorkspaceAwareJob $job, Closure $next): void
    {
        $workspace = Workspace::find($job->workspaceId);

        if (! $workspace) {
            return; // ponytail: log warning; workspace deleted while job queued
        }

        WorkspaceContext::set($workspace);

        $next($job);

        WorkspaceContext::clear();
    }
}
