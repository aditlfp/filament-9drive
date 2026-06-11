<?php

namespace App\Services;

use App\Models\ConnectedAccount;
use Illuminate\Support\Collection;

class GoogleDriveQuotaService
{
    public function __construct(
        protected GoogleDriveClientFactory $clientFactory,
    ) {}

    public function refresh(ConnectedAccount $account): ConnectedAccount
    {
        $drive = $this->clientFactory->make($account);
        $about = $drive->about->get([
            'fields' => 'storageQuota',
        ]);

        $quota = $about->getStorageQuota();

        $account->forceFill([
            'quota_total' => $quota?->getLimit() ? (int) $quota->getLimit() : null,
            'quota_used' => $quota?->getUsage() ? (int) $quota->getUsage() : null,
            'quota_refreshed_at' => now(),
            'status' => 'active',
        ])->save();

        return $account->refresh();
    }

    public function refreshIfStale(ConnectedAccount $account): ConnectedAccount
    {
        if (! $this->isStale($account)) {
            return $account;
        }

        return $this->refresh($account);
    }

    public function isStale(ConnectedAccount $account): bool
    {
        if ($account->quota_refreshed_at === null) {
            return true;
        }

        return $account->quota_refreshed_at->lte(
            now()->subMinutes(config('services.google_drive.quota_cache_minutes', 15))
        );
    }

    /**
     * @return Collection<int, ConnectedAccount>
     */
    public function refreshAllForUser(int $userId, bool $staleOnly = true): Collection
    {
        return ConnectedAccount::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->map(fn (ConnectedAccount $account): ConnectedAccount => $staleOnly
                ? $this->refreshIfStale($account)
                : $this->refresh($account));
    }
}
