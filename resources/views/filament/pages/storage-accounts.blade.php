<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @php $accounts = $this->getAccounts(); @endphp

            @foreach([
                ['label' => 'Total',   'value' => $accounts->count(),                              'icon' => 'heroicon-o-circle-stack',      'color' => 'blue'],
                ['label' => 'Active',  'value' => $accounts->where('status','active')->count(),     'icon' => 'heroicon-o-check-circle',      'color' => 'green'],
                ['label' => 'Issues',  'value' => $accounts->where('status','error')->count(),      'icon' => 'heroicon-o-exclamation-triangle','color' => 'red'],
                ['label' => 'Storage', 'value' => \Illuminate\Support\Number::fileSize($accounts->sum('quota_total') ?? 0), 'icon' => 'heroicon-o-server-stack', 'color' => 'purple'],
            ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                        <x-dynamic-component :component="$stat['icon']" class="h-7 w-7 text-amber-500" />
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Empty state --}}
        @if($accounts->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white py-20 text-center dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-6">
                    <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
                        <defs>
                            <linearGradient id="eg" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#5865f2"/>
                                <stop offset="100%" stop-color="#ec48bd"/>
                            </linearGradient>
                        </defs>
                        <rect x="18" y="50" width="36" height="6" rx="3" fill="url(#eg)" opacity="0.4"/>
                        <rect x="18" y="41" width="36" height="6" rx="3" fill="url(#eg)" opacity="0.6"/>
                        <rect x="18" y="32" width="36" height="6" rx="3" fill="url(#eg)" opacity="0.8"/>
                        <circle cx="47" cy="35" r="1.5" fill="#35ed7e"/>
                        <path d="M12 26a9 9 0 019-9 11 11 0 0121 0 9 9 0 010 18H21a9 9 0 01-9-9z" fill="url(#eg)"/>
                        <path d="M36 30v-8M32 25l4-4 4 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No Storage Accounts Yet</h3>
                <p class="mt-2 max-w-sm text-sm text-gray-500 dark:text-gray-400">Connect your first storage provider to start managing files across multiple cloud services.</p>
            </div>

        @else
            {{-- Account list --}}
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Connected Accounts</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($accounts as $account)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center gap-4">
                            {{-- Provider icon --}}
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg p-1.5
                                {{ $account->provider_type === 'amazon_s3' ? 'bg-[#232F3E]' : ($account->provider_type === 'minio' ? 'bg-[#C72C48]' : 'bg-gray-100 dark:bg-gray-700') }}">
                                @switch($account->provider_type)
                                    @case('google_drive')
                                        <svg viewBox="0 0 87.3 78" class="h-8 w-8"><path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/><path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/><path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/><path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/><path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/><path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/></svg>
                                        @break
                                    @case('amazon_s3')
                                        <svg viewBox="0 0 50 50" class="h-8 w-8"><path fill="#F90" d="M14 35.9c7.7 2.6 16.3 2.5 23.9-.3l.4.7c-7.9 3-16.9 3.1-24.9.3l.6-.7z"/><path fill="#F90" d="M39.7 34.1c-.8-.4-1-.8-.6-1.3l1.5-1.2c.4-.4 1-.3 1.6.2 2.4 2.1 3.7 5 3.3 7.9-.1.5-.5.7-1 .4l-4.8-6z"/><path fill="#F48120" d="M14.4 8.8h3.7v16l10.3-16h4.2L22.4 21.6l10.8 16.5h-4.4L19.7 24.4l-1.7 2.7v10.9h-3.6z"/></svg>
                                        @break
                                    @case('cloudflare_r2')
                                        <svg viewBox="0 0 512 512" class="h-8 w-8"><path fill="#F6821F" d="M323.6 355.5H155.6l-17.4 56.8c-.5 1.6.6 3.2 2.3 3.2h198c1.7 0 2.8-1.6 2.3-3.2l-17.2-56.8z"/><path fill="#FBAD41" d="M323.6 355.5H155.6l17.2-56.5h133.6l17.2 56.5z"/><path fill="#F6821F" d="M416.1 256.7c-3.2-10.4-14.4-15.6-24.6-11.2l-63.4 27.5H183.9l-63.4-27.5c-10.2-4.4-21.4.8-24.6 11.2-3.2 10.3 2.6 21.3 13 24.6l45.9 15.1 199.7.2 45.5-15.3c10.4-3.3 16.3-14.4 16.1-24.6z"/><path fill="#FBAD41" d="M256 96c-60 0-110.3 44.2-118.3 102.1-37.1 4.8-65.7 36.4-65.7 74.8 0 5.1.5 10 1.5 14.8h364.9c1-4.8 1.5-9.7 1.5-14.8 0-38.4-28.7-70-65.6-74.8C366.3 140.2 316 96 256 96z"/></svg>
                                        @break
                                    @case('minio')
                                        <svg viewBox="0 0 283 283" class="h-8 w-8"><path fill="white" d="M141.5 32L32 96v91l109.5 64L251 187V96L141.5 32zm0 20l82 48-36 59.3-46-77-46 77-36-59.3 82-48zM86 212V147l46 77.3L86 212zm110 0l-46 12.3L196 147v65z"/></svg>
                                        @break
                                    @default
                                        <x-heroicon-o-circle-stack class="h-7 w-7 text-gray-500" />
                                @endswitch
                            </div>

                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $account->account_name }}</p>
                                <div class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ Str::headline($account->provider_type) }}</span>
                                    @if($account->last_health_check_at)
                                        <span>Checked {{ $account->last_health_check_at->diffForHumans() }}</span>
                                    @endif
                                    @if($account->quota_total && $account->quota_used !== null)
                                        <span>{{ \Illuminate\Support\Number::fileSize($account->quota_used) }} / {{ \Illuminate\Support\Number::fileSize($account->quota_total) }}</span>
                                    @endif
                                </div>
                                @if($account->quota_total && $account->quota_used !== null)
                                    @php $pct = min(100, ($account->quota_used / max(1, $account->quota_total)) * 100); @endphp
                                    <div class="mt-2 h-1.5 w-48 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-full bg-amber-500" style="width:{{ $pct }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-2">
                            @if($account->health_status === 'healthy')
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">Healthy</span>
                            @elseif($account->health_status === 'unhealthy')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">Unhealthy</span>
                            @endif

                            @if($account->status === 'active')
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">Active</span>
                            @elseif($account->status === 'error')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">Error</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($account->status) }}</span>
                            @endif

                            <button wire:click="testConnection({{ $account->id }})"
                                class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                Test
                            </button>
                            <button wire:click="disconnect({{ $account->id }})"
                                wire:confirm="Disconnect {{ $account->account_name }}? Files remain on provider."
                                class="rounded-lg border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">
                                Remove
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Supported providers footer --}}
        <div class="rounded-xl border border-gray-200 bg-white px-8 py-6 dark:border-gray-700 dark:bg-gray-800">
            <p class="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Supported Providers</p>
            <div class="flex flex-wrap items-center justify-center gap-6">
                @foreach([
                    ['label' => 'Google Drive', 'bg' => 'bg-gray-100 dark:bg-gray-700', 'svg' => '<svg viewBox="0 0 87.3 78" class="h-7 w-7"><path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/><path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/><path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/><path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/><path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/><path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/></svg>'],
                    ['label' => 'Amazon S3',   'bg' => 'bg-[#232F3E]',                  'svg' => '<svg viewBox="0 0 50 50" class="h-7 w-7"><path fill="#F90" d="M14 35.9c7.7 2.6 16.3 2.5 23.9-.3l.4.7c-7.9 3-16.9 3.1-24.9.3l.6-.7z"/><path fill="#F90" d="M39.7 34.1c-.8-.4-1-.8-.6-1.3l1.5-1.2c.4-.4 1-.3 1.6.2 2.4 2.1 3.7 5 3.3 7.9-.1.5-.5.7-1 .4l-4.8-6z"/><path fill="#F48120" d="M14.4 8.8h3.7v16l10.3-16h4.2L22.4 21.6l10.8 16.5h-4.4L19.7 24.4l-1.7 2.7v10.9h-3.6z"/></svg>'],
                    ['label' => 'Cloudflare R2','bg' => 'bg-gray-100 dark:bg-gray-700', 'svg' => '<svg viewBox="0 0 512 512" class="h-7 w-7"><path fill="#F6821F" d="M323.6 355.5H155.6l-17.4 56.8c-.5 1.6.6 3.2 2.3 3.2h198c1.7 0 2.8-1.6 2.3-3.2l-17.2-56.8z"/><path fill="#FBAD41" d="M323.6 355.5H155.6l17.2-56.5h133.6l17.2 56.5z"/><path fill="#F6821F" d="M416.1 256.7c-3.2-10.4-14.4-15.6-24.6-11.2l-63.4 27.5H183.9l-63.4-27.5c-10.2-4.4-21.4.8-24.6 11.2-3.2 10.3 2.6 21.3 13 24.6l45.9 15.1 199.7.2 45.5-15.3c10.4-3.3 16.3-14.4 16.1-24.6z"/><path fill="#FBAD41" d="M256 96c-60 0-110.3 44.2-118.3 102.1-37.1 4.8-65.7 36.4-65.7 74.8 0 5.1.5 10 1.5 14.8h364.9c1-4.8 1.5-9.7 1.5-14.8 0-38.4-28.7-70-65.6-74.8C366.3 140.2 316 96 256 96z"/></svg>'],
                    ['label' => 'MinIO',        'bg' => 'bg-[#C72C48]',                  'svg' => '<svg viewBox="0 0 283 283" class="h-7 w-7"><path fill="white" d="M141.5 32L32 96v91l109.5 64L251 187V96L141.5 32zm0 20l82 48-36 59.3-46-77-46 77-36-59.3 82-48zM86 212V147l46 77.3L86 212zm110 0l-46 12.3L196 147v65z"/></svg>'],
                ] as $p)
                <div class="flex flex-col items-center gap-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $p['bg'] }} p-1.5">{!! $p['svg'] !!}</div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $p['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
