<?php

namespace App\Filament\Pages;

use App\Infrastructure\Context\WorkspaceContext;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Jobs\CheckProviderHealth;
use App\Jobs\SyncProviderQuota;
use App\Models\ConnectedAccount;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitoringConsole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;
    protected static ?string $navigationLabel = 'Monitoring';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 40;
    protected string $view = 'filament.pages.monitoring-console';

    public function mount(): void
    {
        if (! WorkspaceContext::has()) {
            abort(403, 'No workspace available.');
        }
    }

    public function getAccounts(): \Illuminate\Support\Collection
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return collect();

        return ConnectedAccount::where('workspace_id', $ws->id)
            ->orderBy('account_name')
            ->get();
    }

    public function getQueueStats(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $pending = 0;
            $failed  = 0;
        }

        return ['pending' => $pending, 'failed' => $failed];
    }

    public function retryAllFailed(): void
    {
        \Artisan::call('queue:retry', ['id' => ['all']]);
        Notification::make()->success()->title('Retrying all failed jobs')->send();
    }

    public function triggerHealthCheck(int $accountId): void
    {
        CheckProviderHealth::dispatch($accountId);
        Notification::make()->success()->title('Health check queued')->send();
    }

    public function triggerQuotaSync(int $accountId): void
    {
        SyncProviderQuota::dispatch($accountId);
        Notification::make()->success()->title('Quota sync queued')->send();
    }
}
