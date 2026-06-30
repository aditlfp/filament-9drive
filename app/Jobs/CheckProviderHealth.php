<?php

namespace App\Jobs;

use App\Infrastructure\Providers\StorageProviderFactory;
use App\Infrastructure\Queue\WorkspaceAwareJob;
use App\Models\ConnectedAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Periodic health check for connected storage accounts.
 */
class CheckProviderHealth extends WorkspaceAwareJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(
        public int $accountId,
    ) {
        parent::__construct();
    }

    public function handle(StorageProviderFactory $factory): void
    {
        $account = ConnectedAccount::find($this->accountId);

        if (! $account) {
            return;
        }

        try {
            $provider = $factory->make($account);
            $healthy = $provider->healthCheck();

            $account->update([
                'health_status' => $healthy ? 'healthy' : 'unhealthy',
                'last_health_check_at' => now(),
            ]);

            if (! $healthy) {
                Log::warning("Storage account {$account->id} ({$account->account_name}) is unhealthy.");
            }
        } catch (\Throwable $e) {
            $account->update([
                'health_status' => 'unhealthy',
                'last_health_check_at' => now(),
            ]);

            Log::error("Health check failed for account {$account->id}", [
                'error' => $e->getMessage(),
                'account' => $account->account_name,
            ]);
        }
    }
}
