<?php

namespace App\Jobs;

use App\Infrastructure\Providers\StorageProviderFactory;
use App\Infrastructure\Queue\WorkspaceAwareJob;
use App\Models\ConnectedAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncProviderQuota extends WorkspaceAwareJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(public int $accountId)
    {
        parent::__construct();
    }

    public function handle(StorageProviderFactory $factory): void
    {
        $account = ConnectedAccount::find($this->accountId);
        if (! $account) return;

        try {
            $quota = $factory->make($account)->getQuota();

            $account->update([
                'quota_total' => $quota->total,
                'quota_used'  => $quota->used,
                'quota_refreshed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Quota sync failed for account {$account->id}", ['error' => $e->getMessage()]);
        }
    }
}
