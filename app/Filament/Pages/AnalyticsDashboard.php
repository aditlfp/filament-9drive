<?php

namespace App\Filament\Pages;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\ConnectedAccount;
use App\Models\VirtualFile;
use App\Models\Activity;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Analytics';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 20;
    protected string $view = 'filament.pages.analytics-dashboard';

    public function mount(): void
    {
        if (! WorkspaceContext::has()) {
            abort(403, 'No workspace available.');
        }
    }

    public function getStats(): array
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return ['total_files' => 0, 'total_size' => 0, 'total_quota' => 0, 'used_quota' => 0, 'account_count' => 0, 'active_accounts' => 0];

        $files = VirtualFile::where('workspace_id', $ws->id);
        $accounts = ConnectedAccount::where('workspace_id', $ws->id)->get();

        $totalFiles = $files->count();
        $totalSize = $files->sum('size');
        $totalQuota = $accounts->sum('quota_total');
        $usedQuota = $accounts->sum('quota_used');

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_quota' => $totalQuota,
            'used_quota' => $usedQuota,
            'account_count' => $accounts->count(),
            'active_accounts' => $accounts->where('status', 'active')->count(),
        ];
    }

    public function getStorageByProvider(): array
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return [];

        return ConnectedAccount::where('workspace_id', $ws->id)
            ->get()
            ->mapWithKeys(fn ($a) => [
                $a->account_name => [
                    'used' => $a->quota_used ?? 0,
                    'total' => $a->quota_total ?? 0,
                    'percent' => $a->quota_total
                        ? round(($a->quota_used / $a->quota_total) * 100, 1)
                        : null,
                    'provider' => $a->provider_type,
                    'status' => $a->status,
                    'health' => $a->health_status,
                ]
            ])
            ->all();
    }

    public function getUploadTrend(): array
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return [];

        return Activity::where('workspace_id', $ws->id)
            ->where('action', 'upload')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();
    }

    public function getLargestFiles(): \Illuminate\Support\Collection
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return collect();

        return VirtualFile::where('workspace_id', $ws->id)
            ->with('account')
            ->orderByDesc('size')
            ->limit(10)
            ->get();
    }

    public function getMostActiveUsers(): \Illuminate\Support\Collection
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return collect();

        return Activity::where('workspace_id', $ws->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('user_id, COUNT(*) as action_count')
            ->groupBy('user_id')
            ->orderByDesc('action_count')
            ->with('user')
            ->limit(10)
            ->get();
    }
}
