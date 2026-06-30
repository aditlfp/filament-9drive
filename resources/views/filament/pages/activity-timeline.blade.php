<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Filters --}}
        <div class="flex gap-3">
            <select wire:model.live="filterAction"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                @foreach($this->getActionOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Timeline --}}
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @forelse($this->getActivities() as $activity)
                    <li>
                        <div class="relative pb-8">
                            @if(! $loop->last)
                                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-3">
                                {{-- Icon --}}
                                <div class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 ring-8 ring-white dark:bg-gray-700 dark:ring-gray-900">
                                    @switch($activity->action)
                                        @case('upload')
                                            <x-heroicon-o-arrow-up-tray class="h-5 w-5 text-green-500" />
                                            @break
                                        @case('delete')
                                            <x-heroicon-o-trash class="h-5 w-5 text-red-500" />
                                            @break
                                        @case('move')
                                            <x-heroicon-o-arrows-right-left class="h-5 w-5 text-blue-500" />
                                            @break
                                        @case('rename')
                                            <x-heroicon-o-pencil class="h-5 w-5 text-amber-500" />
                                            @break
                                        @case('copy')
                                            <x-heroicon-o-document-duplicate class="h-5 w-5 text-purple-500" />
                                            @break
                                        @case('create_folder')
                                            <x-heroicon-o-folder-plus class="h-5 w-5 text-indigo-500" />
                                            @break
                                        @default
                                            <x-heroicon-o-bolt class="h-5 w-5 text-gray-500" />
                                    @endswitch
                                </div>

                                {{-- Content --}}
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $activity->user?->name ?? 'System' }}
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400 ml-1">
                                            {{ str_replace('_', ' ', $activity->action) }}
                                        </span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300 ml-1">
                                            {{ $activity->resource_name }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <time>{{ $activity->created_at->diffForHumans() }}</time>
                                        @if($activity->ip_address)
                                            <span>{{ $activity->ip_address }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status badge --}}
                                <div class="flex-shrink-0">
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                        {{ ucfirst($activity->action) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No activity recorded yet.
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $this->getActivities()->links() }}
        </div>
    </div>
</x-filament-panels::page>
