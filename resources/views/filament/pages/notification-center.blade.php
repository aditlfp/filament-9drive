<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->getUnreadCount() }} unread</p>
            </div>
            @if($this->getUnreadCount() > 0)
            <button wire:click="markAllRead"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Mark all read
            </button>
            @endif
        </div>

        {{-- Notifications List --}}
        <div class="space-y-2">
            @forelse($this->getNotifications() as $notification)
                <div @class([
                    'rounded-xl border p-4 transition-colors',
                    'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' => $notification->read_at,
                    'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/10' => ! $notification->read_at,
                ])>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/20">
                                @switch($notification->data['type'] ?? 'default')
                                    @case('file_shared')
                                        <x-heroicon-o-share class="h-5 w-5 text-amber-500" />
                                        @break
                                    @case('storage_warning')
                                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-500" />
                                        @break
                                    @case('provider_health')
                                        <x-heroicon-o-signal class="h-5 w-5 text-amber-500" />
                                        @break
                                    @default
                                        <x-heroicon-o-bell class="h-5 w-5 text-amber-500" />
                                @endswitch
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $notification->data['message'] ?? $notification->data['resource_name'] ?? 'Notification' }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @if(! $notification->read_at)
                            <button wire:click="markRead('{{ $notification->id }}')"
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
                                <x-heroicon-o-check class="h-4 w-4" />
                            </button>
                            @endif
                            <button wire:click="deleteNotification('{{ $notification->id }}')"
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-gray-700">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800">
                    <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $this->getNotifications()->links() }}
        </div>
    </div>
</x-filament-panels::page>
