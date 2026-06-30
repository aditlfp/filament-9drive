<?php

namespace App\Filament\Widgets;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\ConnectedAccount;
use App\Models\VirtualFile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkspaceStorageOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $workspace = WorkspaceContext::getOrNull();

        if (! $workspace) {
            return [];
        }

        $accounts = ConnectedAccount::where('workspace_id', $workspace->id)->get();
        $files = VirtualFile::where('workspace_id', $workspace->id)->get();

        $totalQuota = $accounts->sum('quota_total');
        $usedQuota = $accounts->sum('quota_used');
        $fileCount = $files->count();
        $totalSize = $files->sum('size');

        return [
            Stat::make('Storage Accounts', $accounts->count())
                ->description($accounts->where('status', 'active')->count() . ' active')
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color('success'),

            Stat::make('Total Files', number_format($fileCount))
                ->description(\Illuminate\Support\Number::fileSize($totalSize) . ' used')
                ->descriptionIcon('heroicon-o-document')
                ->color('warning'),

            Stat::make('Storage Used', $totalQuota ? round(($usedQuota / $totalQuota) * 100, 1) . '%' : 'N/A')
                ->description($totalQuota ? \Illuminate\Support\Number::fileSize($usedQuota) . ' / ' . \Illuminate\Support\Number::fileSize($totalQuota) : 'Quota unknown')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}
