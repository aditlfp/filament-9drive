<?php

namespace App\Filament\Pages;

use App\Infrastructure\Context\WorkspaceContext;
use App\Models\ConnectedAccount;
use App\Models\UploadPolicy;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UploadPolicyBuilder extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;
    protected static ?string $navigationLabel = 'Upload Policies';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 30;
    protected string $view = 'filament.pages.upload-policy-builder';

    // Policy form state
    public ?int $editingPolicyId = null;
    public string $policyName = '';
    public string $strategy = 'round_robin';
    public bool $policyActive = true;
    public array $rules = [];

    // New rule form state
    public string $ruleCondition = 'mime_prefix';
    public string $ruleValue = '';
    public ?int $ruleAccountId = null;

    public function mount(): void
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return;
        $policy = UploadPolicy::where('workspace_id', $ws->id)->first();

        if ($policy) {
            $this->editingPolicyId = $policy->id;
            $this->policyName = $policy->name;
            $this->strategy = $policy->strategy;
            $this->policyActive = (bool) $policy->is_active;
            $this->rules = $policy->rules ?? [];
        }
    }

    public function addRule(): void
    {
        if (! $this->ruleValue || ! $this->ruleAccountId) {
            Notification::make()->warning()->title('Fill condition value and target account')->send();
            return;
        }

        $this->rules[] = [
            'condition' => $this->ruleCondition,
            'value' => $this->ruleValue,
            'account_id' => $this->ruleAccountId,
        ];

        $this->ruleValue = '';
        $this->ruleAccountId = null;
    }

    public function removeRule(int $index): void
    {
        array_splice($this->rules, $index, 1);
    }

    public function moveRuleUp(int $index): void
    {
        if ($index === 0) return;
        [$this->rules[$index - 1], $this->rules[$index]] = [$this->rules[$index], $this->rules[$index - 1]];
    }

    public function moveRuleDown(int $index): void
    {
        if ($index >= count($this->rules) - 1) return;
        [$this->rules[$index], $this->rules[$index + 1]] = [$this->rules[$index + 1], $this->rules[$index]];
    }

    public function save(): void
    {
        if (! $this->policyName) {
            Notification::make()->warning()->title('Policy name required')->send();
            return;
        }

        $ws = WorkspaceContext::getOrNull();
        if (! $ws) { Notification::make()->warning()->title('No workspace')->send(); return; }

        $data = [
            'workspace_id' => $ws->id,
            'name' => $this->policyName,
            'strategy' => $this->strategy,
            'is_active' => $this->policyActive,
            'rules' => $this->rules,
        ];

        $policy = $this->editingPolicyId
            ? UploadPolicy::find($this->editingPolicyId)
            : new UploadPolicy();

        $policy->fill($data)->save();
        $this->editingPolicyId = $policy->id;

        Notification::make()->success()->title('Policy saved')->send();
    }

    public function getAccounts(): \Illuminate\Support\Collection
    {
        $ws = WorkspaceContext::getOrNull();
        if (! $ws) return collect();
        return ConnectedAccount::where('workspace_id', $ws->id)
            ->where('status', 'active')
            ->get();
    }

    public function getConditionOptions(): array
    {
        return [
            'mime_prefix' => 'MIME starts with',
            'mime_exact' => 'MIME equals',
            'extension' => 'File extension',
            'size_lt' => 'File size less than (bytes)',
            'size_gte' => 'File size at least (bytes)',
            'default' => 'Default (catch-all)',
        ];
    }

    public function getStrategyOptions(): array
    {
        return [
            'round_robin' => 'Round Robin',
            'least_occupied' => 'Least Occupied',
            'weighted' => 'Weighted',
            'rule_engine' => 'Rule Engine',
        ];
    }
}
