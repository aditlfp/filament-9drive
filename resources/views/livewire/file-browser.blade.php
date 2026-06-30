<div
    x-data="{
        view: $wire.entangle('view'),
        showContextMenu: false,
        contextMenuX: 0,
        contextMenuY: 0,
        contextTarget: null,
        contextType: null,
        openContext(e, id, type) {
            e.preventDefault();
            this.contextTarget = id;
            this.contextType = type;
            this.contextMenuX = e.clientX;
            this.contextMenuY = e.clientY;
            this.showContextMenu = true;
        },
        closeContext() { this.showContextMenu = false; }
    }"
    @click.away="closeContext"
    @keydown.escape.window="closeContext"
    class="flex h-[calc(100vh-8rem)] overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
    wire:loading.class="opacity-75"
>
    {{-- ── Sidebar ─────────────────────────────────── --}}
    <aside class="hidden w-56 flex-shrink-0 border-r border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 lg:flex lg:flex-col">
        <div class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Folders
        </div>
        <div class="flex-1 overflow-y-auto px-2 pb-4">
            {{-- Root --}}
            <button wire:click="goToRoot"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-200 dark:text-gray-200 dark:hover:bg-gray-700">
                <x-heroicon-o-home class="h-4 w-4 text-amber-500" />
                <span>Root</span>
            </button>
            {{-- Tree --}}
            @foreach($this->sidebarTree as $folder)
                <button wire:click="openFolder({{ $folder->id }})"
                    class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-200 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-folder class="h-4 w-4 text-amber-400" />
                    <span class="truncate">{{ $folder->name }}</span>
                </button>
            @endforeach
        </div>
    </aside>

    {{-- ── Main Area ───────────────────────────────── --}}
    <div class="flex flex-1 flex-col overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-2 dark:border-gray-700">
            {{-- Breadcrumbs --}}
            <nav class="flex flex-1 items-center gap-1 text-sm overflow-x-auto">
                <button wire:click="goToRoot" class="text-gray-500 hover:text-amber-500 flex-shrink-0">
                    <x-heroicon-o-home class="h-4 w-4" />
                </button>
                @foreach($breadcrumbs as $crumb)
                    <span class="text-gray-400">/</span>
                    <button wire:click="openFolder({{ $crumb['id'] }})" class="truncate max-w-[120px] text-gray-600 hover:text-amber-500 dark:text-gray-300">
                        {{ $crumb['name'] }}
                    </button>
                @endforeach
                @if($this->currentFolder)
                    <span class="text-gray-400">/</span>
                    <span class="font-medium text-gray-800 dark:text-white truncate max-w-[140px]">{{ $this->currentFolder->name }}</span>
                @endif
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-1.5 flex-shrink-0">
                {{-- Upload --}}
                <button wire:click="openUploadModal"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                    <x-heroicon-o-arrow-up-tray class="h-3.5 w-3.5" />Upload
                </button>
                {{-- New folder --}}
                <button wire:click="openNewFolderModal"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-folder-plus class="h-3.5 w-3.5" />New Folder
                </button>
                {{-- Delete selected --}}
                @if(count($selectedFileIds) > 0)
                <button wire:click="deleteSelected" wire:confirm="Delete {{ count($selectedFileIds) }} file(s)?"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20">
                    <x-heroicon-o-trash class="h-3.5 w-3.5" />Delete ({{ count($selectedFileIds) }})
                </button>
                @endif
                {{-- View toggle --}}
                <div class="flex rounded-lg border border-gray-300 dark:border-gray-600">
                    <button wire:click="setView('grid')" @class(['rounded-l-lg px-2 py-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700', 'bg-amber-50 text-amber-500 dark:bg-amber-900/20' => $view === 'grid'])>
                        <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                    </button>
                    <button wire:click="setView('list')" @class(['rounded-r-lg px-2 py-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700', 'bg-amber-50 text-amber-500 dark:bg-amber-900/20' => $view === 'list'])>
                        <x-heroicon-o-list-bullet class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- File Area --}}
        <div class="flex-1 overflow-y-auto p-4" wire:loading.class="pointer-events-none">

            {{-- Loading overlay --}}
            <div wire:loading wire:target="openFolder,goBack,goToRoot,deleteSelected,uploadFiles"
                class="flex items-center justify-center py-20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-amber-500 border-t-transparent"></div>
            </div>

            {{-- Grid View --}}
            <div wire:loading.remove>
                {{-- Empty state --}}
                @if($this->folders->isEmpty() && $this->files->isEmpty())
                    <div class="flex flex-col items-center justify-center py-24 text-center">
                        <x-heroicon-o-folder-open class="h-16 w-16 text-gray-300 dark:text-gray-600" />
                        <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">This folder is empty</p>
                        <button wire:click="openUploadModal" class="mt-3 text-sm text-amber-500 hover:underline">Upload files</button>
                    </div>
                @else

                @if($view === 'grid')
                    {{-- Grid: Folders --}}
                    @if($this->folders->isNotEmpty())
                    <div class="mb-4">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Folders</h3>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                            @foreach($this->folders as $folder)
                            <div
                                wire:key="folder-{{ $folder->id }}"
                                @contextmenu="openContext($event, {{ $folder->id }}, 'folder')"
                                wire:dblclick="openFolder({{ $folder->id }})"
                                class="group relative flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-transparent p-3 text-center hover:border-amber-200 hover:bg-amber-50 dark:hover:border-amber-800 dark:hover:bg-amber-900/10"
                            >
                                <x-heroicon-o-folder class="h-12 w-12 text-amber-400 group-hover:text-amber-500" />
                                <span class="line-clamp-2 text-xs font-medium text-gray-700 dark:text-gray-200">{{ $folder->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Grid: Files --}}
                    @if($this->files->isNotEmpty())
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Files</h3>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                            @foreach($this->files as $file)
                            <div
                                wire:key="file-{{ $file->id }}"
                                @contextmenu="openContext($event, {{ $file->id }}, 'file')"
                                wire:click="toggleSelection({{ $file->id }})"
                                wire:dblclick="openPreview({{ $file->id }})"
                                @class([
                                    'group relative flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border p-3 text-center transition-all',
                                    'border-amber-400 bg-amber-50 ring-1 ring-amber-400 dark:border-amber-600 dark:bg-amber-900/20' => in_array($file->id, $selectedFileIds),
                                    'border-transparent hover:border-gray-200 hover:bg-gray-50 dark:hover:border-gray-700 dark:hover:bg-gray-800' => !in_array($file->id, $selectedFileIds),
                                ])
                            >
                                @if($file->isImage() && $file->metadata['thumbnail_url'] ?? null)
                                    <img src="{{ $file->metadata['thumbnail_url'] }}" alt="{{ $file->name }}"
                                        class="h-12 w-12 rounded object-cover" />
                                @else
                                    <x-heroicon-o-document class="h-12 w-12 {{ $this->fileIconColor($file->mime_type) }}" />
                                @endif
                                <span class="line-clamp-2 w-full text-xs font-medium text-gray-700 dark:text-gray-200">{{ $file->name }}</span>
                                <span class="text-[10px] text-gray-400">{{ $file->formatted_size }}</span>
                                {{-- Favorite --}}
                                <button
                                    wire:click.stop="toggleFavorite({{ $file->id }})"
                                    class="absolute right-1.5 top-1.5 opacity-0 group-hover:opacity-100 {{ $file->is_favorite ? '!opacity-100' : '' }}"
                                >
                                    @if($file->is_favorite)
                                        <x-heroicon-s-star class="h-3.5 w-3.5 text-amber-400" />
                                    @else
                                        <x-heroicon-o-star class="h-3.5 w-3.5 text-gray-400" />
                                    @endif
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                @else
                    {{-- List View --}}
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 pl-2 font-medium text-gray-500 dark:text-gray-400 w-6">
                                    <input type="checkbox" wire:click="selectAll" class="rounded border-gray-300" />
                                </th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-gray-400">Name</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Provider</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Size</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-gray-400 hidden lg:table-cell">Modified</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->folders as $folder)
                            <tr
                                wire:key="folder-row-{{ $folder->id }}"
                                @contextmenu="openContext($event, {{ $folder->id }}, 'folder')"
                                wire:dblclick="openFolder({{ $folder->id }})"
                                class="group cursor-pointer border-b border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                            >
                                <td class="py-2 pl-2"><span class="w-4 h-4 block"></span></td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-folder class="h-5 w-5 flex-shrink-0 text-amber-400" />
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $folder->name }}</span>
                                    </div>
                                </td>
                                <td class="py-2 hidden sm:table-cell text-gray-500">—</td>
                                <td class="py-2 hidden md:table-cell text-gray-500">—</td>
                                <td class="py-2 hidden lg:table-cell text-gray-500">{{ $folder->updated_at->diffForHumans() }}</td>
                                <td class="py-2"></td>
                            </tr>
                            @endforeach

                            @foreach($this->files as $file)
                            <tr
                                wire:key="file-row-{{ $file->id }}"
                                @contextmenu="openContext($event, {{ $file->id }}, 'file')"
                                wire:click="toggleSelection({{ $file->id }})"
                                wire:dblclick="openPreview({{ $file->id }})"
                                @class([
                                    'group cursor-pointer border-b border-gray-100 dark:border-gray-800',
                                    'bg-amber-50 dark:bg-amber-900/10' => in_array($file->id, $selectedFileIds),
                                    'hover:bg-gray-50 dark:hover:bg-gray-800' => !in_array($file->id, $selectedFileIds),
                                ])
                            >
                                <td class="py-2 pl-2">
                                    <input type="checkbox"
                                        wire:click.stop="toggleSelection({{ $file->id }})"
                                        @checked(in_array($file->id, $selectedFileIds))
                                        class="rounded border-gray-300" />
                                </td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-document class="h-5 w-5 flex-shrink-0 {{ $this->fileIconColor($file->mime_type) }}" />
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $file->name }}</span>
                                        @if($file->is_favorite)
                                            <x-heroicon-s-star class="h-3.5 w-3.5 text-amber-400 flex-shrink-0" />
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2 hidden sm:table-cell text-gray-500 text-xs">{{ $file->account?->account_name ?? '—' }}</td>
                                <td class="py-2 hidden md:table-cell text-gray-500 text-xs">{{ $file->formatted_size }}</td>
                                <td class="py-2 hidden lg:table-cell text-gray-500 text-xs">{{ $file->updated_at->diffForHumans() }}</td>
                                <td class="py-2">
                                    <button wire:click.stop="toggleFavorite({{ $file->id }})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-amber-400">
                                        <x-heroicon-o-star class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ── Context Menu ────────────────────────────── --}}
    <div
        x-show="showContextMenu"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        :style="{ top: contextMenuY + 'px', left: contextMenuX + 'px' }"
        class="fixed z-50 min-w-[160px] rounded-xl border border-gray-200 bg-white py-1 shadow-xl dark:border-gray-700 dark:bg-gray-800"
        @click.stop
    >
        <template x-if="contextType === 'folder'">
            <div>
                <button @click="$wire.openFolder(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-folder-open class="h-4 w-4" />Open
                </button>
                <hr class="my-1 border-gray-100 dark:border-gray-700">
                <button @click="$wire.deleteFolder(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <x-heroicon-o-trash class="h-4 w-4" />Delete
                </button>
            </div>
        </template>
        <template x-if="contextType === 'file'">
            <div>
                <button @click="$wire.openPreview(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-eye class="h-4 w-4" />Preview
                </button>
                <button @click="$wire.toggleFavorite(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-star class="h-4 w-4" />Favorite
                </button>
                <button @click="$wire.createShare(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-share class="h-4 w-4" />Share
                </button>
                <hr class="my-1 border-gray-100 dark:border-gray-700">
                <button @click="$wire.toggleSelection(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-o-check-circle class="h-4 w-4" />Select
                </button>
                <hr class="my-1 border-gray-100 dark:border-gray-700">
                <button @click="$wire.deleteFile(contextTarget); closeContext()"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <x-heroicon-o-trash class="h-4 w-4" />Delete
                </button>
            </div>
        </template>
    </div>

    {{-- ── New Folder Modal ────────────────────────── --}}
    <x-filament::modal wire:model="showNewFolderModal" id="new-folder-modal" :close-button="true">
        <x-slot name="heading">New Folder</x-slot>
        <div class="mt-2">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="newFolderName"
                    placeholder="Folder name"
                    wire:keydown.enter="createFolder"
                    autofocus
                />
            </x-filament::input.wrapper>
        </div>
        <x-slot name="footerActions">
            <x-filament::button wire:click="createFolder">Create</x-filament::button>
            <x-filament::button color="gray" wire:click="$set('showNewFolderModal', false)">Cancel</x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- ── Upload Modal ────────────────────────────── --}}
    <x-filament::modal wire:model="showUploadModal" id="upload-modal" :close-button="true" width="xl">
        <x-slot name="heading">Upload Files</x-slot>
        <div class="mt-2 flex flex-col gap-3">
            <div
                x-data="{ dragging: false }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false"
                :class="dragging ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/10' : 'border-gray-300 dark:border-gray-600'"
                class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 text-center transition-colors"
            >
                <x-heroicon-o-cloud-arrow-up class="h-10 w-10 text-gray-400 mb-2" />
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Drag & drop files or</p>
                <label class="mt-2 cursor-pointer text-sm text-amber-500 hover:underline">
                    browse
                    <input type="file" multiple wire:model="uploadQueue" class="sr-only" />
                </label>
            </div>

            @if(!empty($uploadQueue))
            <div class="space-y-1">
                @foreach($uploadQueue as $i => $file)
                    @if($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile || $file instanceof \Illuminate\Http\UploadedFile)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-1.5 text-xs dark:bg-gray-800">
                        <span class="truncate max-w-[280px] text-gray-700 dark:text-gray-200">{{ $file->getClientOriginalName() }}</span>
                        <span class="text-gray-400">{{ number_format($file->getSize() / 1024, 1) }} KB</span>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            <div wire:loading wire:target="uploadQueue">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <div class="h-3 w-3 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
                    Preparing upload…
                </div>
            </div>
        </div>
        <x-slot name="footerActions">
            <x-filament::button wire:click="uploadFiles" wire:loading.attr="disabled" wire:target="uploadFiles">
                <span wire:loading.remove wire:target="uploadFiles">Upload</span>
                <span wire:loading wire:target="uploadFiles">Uploading…</span>
            </x-filament::button>
            <x-filament::button color="gray" wire:click="$set('showUploadModal', false)">Cancel</x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- ── Preview Modal ───────────────────────────── --}}
    <x-filament::modal wire:model="showPreviewModal" id="preview-modal" :close-button="true" width="2xl">
        <x-slot name="heading">
            {{ $this->previewFile?->name ?? 'Preview' }}
        </x-slot>
        @if($this->previewFile)
            <div class="flex items-center justify-center min-h-[200px] max-h-[70vh] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                @if($this->previewFile->isImage())
                    <img src="{{ route('file.preview.image', $this->previewFile->id) }}"
                        alt="{{ $this->previewFile->name }}"
                        class="max-h-[65vh] max-w-full object-contain rounded" />
                @elseif($this->previewFile->isPdf())
                    <iframe src="{{ route('file.preview.image', $this->previewFile->id) }}"
                        class="h-[65vh] w-full rounded" />
                @else
                    <div class="flex flex-col items-center gap-2 py-10 text-gray-400">
                        <x-heroicon-o-document class="h-16 w-16" />
                        <p class="text-sm">Preview not available for this file type</p>
                    </div>
                @endif
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div class="flex gap-4">
                    <span>{{ $this->previewFile->formatted_size }}</span>
                    <span>{{ $this->previewFile->mime_type }}</span>
                    <span>{{ $this->previewFile->account?->account_name }}</span>
                </div>
                <span>{{ $this->previewFile->updated_at->diffForHumans() }}</span>
            </div>
        @endif
    </x-filament::modal>
</div>
