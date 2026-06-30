<?php

namespace App\Infrastructure\Search;

use App\Domain\Contracts\SearchServiceInterface;
use App\Domain\Contracts\StorageProviderInterface;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Models\ConnectedAccount;
use App\Models\VirtualFile;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedSearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly StorageProviderFactory $factory,
    ) {}

    public function local(Workspace $workspace, string $query): Collection
    {
        return VirtualFile::query()
            ->where('workspace_id', $workspace->id)
            ->where('name', 'like', "%{$query}%")
            ->with(['folder', 'account'])
            ->limit(100)
            ->get();
    }

    public function everywhere(Workspace $workspace, string $query): Collection
    {
        $local = $this->local($workspace, $query);

        $accounts = ConnectedAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->get();

        // Fan out to all providers concurrently
        $remote = $accounts->map(function (ConnectedAccount $account) use ($query) {
            return $this->searchAccount($account, $query);
        })->flatten();

        // Merge local + remote, dedupe by provider_file_id
        $all = $local->concat($remote)->unique('provider_file_id');

        return $all->sortBy('name')->values();
    }

    public function provider(Workspace $workspace, string $providerType, string $query): Collection
    {
        $accounts = ConnectedAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->where('provider_type', $providerType)
            ->get();

        return $accounts->map(fn ($a) => $this->searchAccount($a, $query))->flatten();
    }

    private function searchAccount(ConnectedAccount $account, string $query): Collection
    {
        try {
            $provider = $this->factory->make($account);
            $results = $provider->search($query);

            return collect($results);
        } catch (\Throwable $e) {
            Log::error("Search failed for account {$account->id}", ['error' => $e->getMessage()]);
            return collect();
        }
    }
}
