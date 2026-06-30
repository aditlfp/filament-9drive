<?php

namespace App\Jobs;

use App\Infrastructure\Queue\WorkspaceAwareJob;
use App\Models\ConnectedAccount;
use App\Models\Workspace;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Dispatched by the scheduler. Fans out per-account health + quota jobs.
 */
class RunWorkspaceHealthChecks extends WorkspaceAwareJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(public int $wsId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        ConnectedAccount::where('workspace_id', $this->wsId)
            ->where('status', 'active')
            ->pluck('id')
            ->each(function (int $id) {
                CheckProviderHealth::dispatch($id);
                SyncProviderQuota::dispatch($id);
            });
    }
}
