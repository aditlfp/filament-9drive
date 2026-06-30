<?php

namespace App\Infrastructure\Rules;

use App\Models\ConnectedAccount;
use App\Models\UploadPolicy;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;

/**
 * Evaluates upload policy rules to select the target ConnectedAccount.
 *
 * Rule JSON shape (stored in upload_policies.rules):
 * [
 *   { "condition": "mime_prefix", "value": "image/", "account_id": 5 },
 *   { "condition": "mime_prefix", "value": "video/", "account_id": 7 },
 *   { "condition": "size_lt",     "value": 104857600, "account_id": 3 },
 *   { "condition": "extension",   "value": "pdf",     "account_id": 4 },
 * ]
 *
 * Conditions:
 *   mime_prefix  – str_starts_with(mime, value)
 *   mime_exact   – mime === value
 *   extension    – file extension === value
 *   size_lt      – size < value (bytes)
 *   size_gte     – size >= value (bytes)
 *   default      – always matches (fallback)
 */
final class UploadRuleEngine
{
    public function evaluate(Workspace $workspace, UploadedFile $file): ConnectedAccount
    {
        $policy = UploadPolicy::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        // No policy or no rules → fall back to quota-based selection
        if (! $policy || empty($policy->rules)) {
            return $this->fallbackAccount($workspace, $file->getSize());
        }

        foreach ($policy->rules as $rule) {
            if ($this->matches($rule, $file)) {
                $account = ConnectedAccount::find($rule['account_id'] ?? null);
                if ($account && $account->workspace_id === $workspace->id && $account->status === 'active') {
                    return $account;
                }
            }
        }

        return $this->fallbackAccount($workspace, $file->getSize());
    }

    private function matches(array $rule, UploadedFile $file): bool
    {
        $condition = $rule['condition'] ?? 'default';
        $value = $rule['value'] ?? null;

        return match ($condition) {
            'mime_prefix' => str_starts_with($file->getMimeType() ?? '', (string) $value),
            'mime_exact'  => ($file->getMimeType() ?? '') === (string) $value,
            'extension'   => strtolower($file->getClientOriginalExtension()) === strtolower((string) $value),
            'size_lt'     => $file->getSize() < (int) $value,
            'size_gte'    => $file->getSize() >= (int) $value,
            'default'     => true,
            default       => false,
        };
    }

    private function fallbackAccount(Workspace $workspace, int $size): ConnectedAccount
    {
        $account = ConnectedAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->get()
            ->filter(fn ($a) => $a->quota_available === null || $a->quota_available >= $size)
            ->sortByDesc(fn ($a) => $a->quota_available ?? PHP_INT_MAX)
            ->first();

        return $account ?? throw new \RuntimeException('No active storage account available for this workspace.');
    }
}
