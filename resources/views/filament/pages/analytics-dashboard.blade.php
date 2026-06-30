<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            @foreach([
                ['label' => 'Total Files', 'value' => number_format($this->getStats()['total_files']), 'icon' => 'heroicon-o-document'],
                ['label' => 'Total Size', 'value' => \Illuminate\Support\Number::fileSize($this->getStats()['total_size']), 'icon' => 'heroicon-o-circle-stack'],
                ['label' => 'Storage Used', 'value' => ($this->getStats()['total_quota'] ? round(($this->getStats()['used_quota'] / $this->getStats()['total_quota']) * 100, 1) . '%' : 'N/A'), 'icon' => 'heroicon-o-chart-bar'],
                ['label' => 'Active Accounts', 'value' => $this->getStats()['active_accounts'] . ' / ' . $this->getStats()['account_count'], 'icon' => 'heroicon-o-check-circle'],
            ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                        <x-dynamic-component :component="$stat['icon']" class="h-6 w-6 text-amber-500" />
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Storage by Provider --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Storage by Provider</h3>
            <div class="space-y-4">
                @foreach($this->getStorageByProvider() as $name => $data)
                <div>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $name }}</span>
                        <span class="text-gray-500">{{ $data['percent'] ? $data['percent'] . '%' : 'N/A' }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full bg-amber-500" style="width: {{ min(100, $data['percent'] ?? 0) }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ \Illuminate\Support\Number::fileSize($data['used']) }} / {{ \Illuminate\Support\Number::fileSize($data['total']) }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Largest Files --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Largest Files</h3>
            <div class="space-y-2">
                @foreach($this->getLargestFiles() as $file)
                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document class="h-4 w-4 text-gray-400" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $file->name }}</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ $file->formatted_size }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
