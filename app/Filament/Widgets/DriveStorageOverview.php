<?php

namespace App\Filament\Widgets;

use App\Models\ConnectedAccount;
use App\Models\File;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DriveStorageOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'Storage overview';

    protected ?string $description = 'Combined Google Drive capacity for your connected accounts.';

    public function getLabel(): string
    {
        return 'Storage overview';
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        $accounts = ConnectedAccount::query()
            ->where('user_id', $userId)
            ->get(['id', 'quota_total', 'quota_used', 'quota_refreshed_at', 'status']);

        $knownQuotaAccounts = $accounts->filter(fn (ConnectedAccount $account): bool => $account->quota_total !== null);

        $totalStorage = (int) $knownQuotaAccounts->sum('quota_total');
        $usedStorage = (int) $knownQuotaAccounts->sum(fn (ConnectedAccount $account): int => $account->quota_used ?? 0);
        $availableStorage = max(0, $totalStorage - $usedStorage);
        $usedPercentage = $totalStorage > 0 ? round($usedStorage / $totalStorage * 100, 2) : null;
        $uploadedBytes = (int) File::query()->ownedBy($userId)->sum('size');
        $unknownAccounts = $accounts->count() - $knownQuotaAccounts->count();
        $lastRefresh = $accounts
            ->pluck('quota_refreshed_at')
            ->filter()
            ->sortDesc()
            ->first();

        return [
            Stat::make('Total storage', self::formatBytes($totalStorage))
                ->description($unknownAccounts > 0 ? "{$unknownAccounts} account(s) need quota refresh" : 'Across all connected accounts')
                ->descriptionColor($unknownAccounts > 0 ? 'warning' : 'gray')
                ->icon(Heroicon::OutlinedCircleStack)
                ->color('primary'),
            Stat::make('Used storage', self::formatBytes($usedStorage))
                ->description($usedPercentage === null ? 'Quota unknown' : "{$usedPercentage}% of combined quota")
                ->descriptionColor($usedPercentage !== null && $usedPercentage >= 85 ? 'danger' : 'gray')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color($usedPercentage !== null && $usedPercentage >= 85 ? 'danger' : 'info'),
            Stat::make('Available storage', self::formatBytes($availableStorage))
                ->description($lastRefresh ? 'Last refreshed '.$lastRefresh->diffForHumans() : 'Refresh account quotas for live capacity')
                ->descriptionColor($lastRefresh ? 'gray' : 'warning')
                ->icon(Heroicon::OutlinedCloud)
                ->color('success'),
            Stat::make('Drive accounts', (string) $accounts->count())
                ->description($accounts->where('status', 'active')->count().' active')
                ->icon(Heroicon::OutlinedLink)
                ->color('gray'),
            Stat::make('Tracked files', (string) File::query()->ownedBy($userId)->count())
                ->description(self::formatBytes($uploadedBytes).' uploaded through 9drive')
                ->icon(Heroicon::OutlinedDocument)
                ->color('gray'),
        ];
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($index = 0; $bytes >= 1024 && $index < count($units) - 1; $index++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
