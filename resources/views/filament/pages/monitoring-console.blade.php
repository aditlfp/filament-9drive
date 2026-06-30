<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Queue Stats --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach([
                ['label' => 'Pending Jobs', 'value' => $this->getQueueStats()['pending'], 'color' => 'blue'],
                ['label' => 'Failed Jobs', 'value' => $this->getQueueStats()['failed'], 'color' => 'red'],
                ['label' => 'Storage Accounts', 'value' => $this->getAccounts()->count(), 'color' => 'green'],
            ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-3xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button wire:click="retryAllFailed"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                <x-heroicon-o-arrow-path class="h-4 w-4" />Retry All Failed
            </button>
        </div>

        {{-- Provider Health --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Provider Health</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($this->getAccounts() as $account)
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                            <x-heroicon-o-circle-stack class="h-6 w-6 text-gray-500" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $account->account_name }}</p>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ Str::headline($account->provider_type) }}</span>
                                @if($account->last_health_check_at)
                                    <span>Checked {{ $account->last_health_check_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($account->health_status === 'healthy')
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">Healthy</span>
                        @else
                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">Unhealthy</span>
                        @endif
                        <button wire:click="triggerHealthCheck({{ $account->id }})"
                            class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Check Now
                        </button>
                        <button wire:click="triggerQuotaSync({{ $account->id }})"
                            class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Sync Quota
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
