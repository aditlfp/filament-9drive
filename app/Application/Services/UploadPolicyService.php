<?php

namespace App\Application\Services;

use App\Models\ConnectedAccount;
use App\Models\Workspace;
use RuntimeException;

class UploadPolicyService
{
    /**
     * Select best account for upload based on workspace policy.
     * ponytail: weighted/rule_engine strategies; add when policies table is configured per workspace.
     */
    public function selectAccount(Workspace $workspace, int $fileSizeBytes): ConnectedAccount
    {
        $accounts = ConnectedAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->get();

        if ($accounts->isEmpty()) {
            throw new RuntimeException('No active storage accounts connected to this workspace.');
        }

        $policy = $workspace->uploadPolicies()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        $strategy = $policy?->strategy ?? 'least_occupied';

        return match ($strategy) {
            'round_robin' => $this->roundRobin($accounts),
            'least_occupied' => $this->leastOccupied($accounts, $fileSizeBytes),
            'weighted' => $this->weighted($accounts, $policy?->weights ?? []),
            default => $this->leastOccupied($accounts, $fileSizeBytes),
        };
    }

    private function leastOccupied($accounts, int $size): ConnectedAccount
    {
        $eligible = $accounts->filter(
            fn (ConnectedAccount $a) => $a->quota_available === null || $a->quota_available >= $size
        );

        if ($eligible->isEmpty()) {
            throw new RuntimeException('No account has sufficient quota for this file.');
        }

        return $eligible->sortByDesc(
            fn (ConnectedAccount $a) => $a->quota_available ?? PHP_INT_MAX
        )->first();
    }

    private function roundRobin($accounts): ConnectedAccount
    {
        // ponytail: persist last-used index; add when multi-account round-robin matters
        return $accounts->random();
    }

    private function weighted($accounts, array $weights): ConnectedAccount
    {
        if (empty($weights)) {
            return $this->roundRobin($accounts);
        }

        $total = array_sum($weights);
        $rand = mt_rand(1, $total);
        $cumulative = 0;

        foreach ($accounts as $account) {
            $cumulative += $weights[$account->id] ?? 0;
            if ($rand <= $cumulative) {
                return $account;
            }
        }

        return $accounts->first();
    }
}
