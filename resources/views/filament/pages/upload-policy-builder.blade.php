<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Policy Header --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Policy Name</label>
                    <input type="text" wire:model="policyName"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Strategy</label>
                    <select wire:model="strategy"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($this->getStrategyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="policyActive" id="active" class="rounded" />
                    <label for="active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>
        </div>

        {{-- Rules Builder --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Upload Rules</h3>
            
            {{-- Add Rule Form --}}
            <div class="mb-4 grid grid-cols-1 gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-700 md:grid-cols-4">
                <select wire:model="ruleCondition"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @foreach($this->getConditionOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model="ruleValue" placeholder="Value"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                <select wire:model="ruleAccountId"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="">Select account...</option>
                    @foreach($this->getAccounts() as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                    @endforeach
                </select>
                <button wire:click="addRule"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                    Add Rule
                </button>
            </div>

            {{-- Rules List --}}
            <div class="space-y-2">
                @forelse($rules as $index => $rule)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                            {{ $index + 1 }}
                        </span>
                        <div class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $this->getConditionOptions()[$rule['condition']] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">: {{ $rule['value'] }}</span>
                            <span class="text-gray-400 dark:text-gray-500">→</span>
                            <span class="font-medium text-amber-600 dark:text-amber-400">
                                {{ $this->getAccounts()->firstWhere('id', $rule['account_id'])?->account_name ?? 'Unknown' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="moveRuleUp({{ $index }})"
                            @class(['rounded p-1 hover:bg-gray-100 dark:hover:bg-gray-700', 'text-gray-400 cursor-not-allowed' => $index === 0, 'text-gray-600 dark:text-gray-300' => $index > 0])>
                            <x-heroicon-o-chevron-up class="h-4 w-4" />
                        </button>
                        <button wire:click="moveRuleDown({{ $index }})"
                            @class(['rounded p-1 hover:bg-gray-100 dark:hover:bg-gray-700', 'text-gray-400 cursor-not-allowed' => $index === count($rules) - 1, 'text-gray-600 dark:text-gray-300' => $index < count($rules) - 1])>
                            <x-heroicon-o-chevron-down class="h-4 w-4" />
                        </button>
                        <button wire:click="removeRule({{ $index }})"
                            class="rounded p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                @empty
                <p class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    No rules yet. Add your first rule above.
                </p>
                @endforelse
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end">
            <button wire:click="save"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-6 py-2 font-semibold text-white hover:bg-amber-600">
                <x-heroicon-o-check class="h-4 w-4" />Save Policy
            </button>
        </div>
    </div>
</x-filament-panels::page>
