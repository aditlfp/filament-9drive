<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\File;
use App\Models\Folder;

new class extends Component {
    public ?int $folderId = null;
    public array $breadcrumbs = []; // ✅ untuk navigasi back
    public string $view = 'grid';
    public bool $showNewFolderModal = false;
    public string $newFolderName = '';
    public ?int $previewFileId = null;
    public bool $showPreviewModal = false;

    public function openFolder(int $folderId): void
    {
        // ✅ simpan history breadcrumb
        $folder = Folder::find($folderId);
        $this->breadcrumbs[] = [
            'id' => $this->folderId,
            'name' => empty($this->breadcrumbs) ? 'Root' : end($this->breadcrumbs)['name'] ?? 'Root',
        ];
        // Sebenarnya kita simpan nama folder yang sedang aktif sebelum pindah
        // Reset & rebuild lebih bersih:
        $this->folderId = $folderId;
    }

    public function openPreview(int $fileId): void
    {
        $file = File::with('storageAccount')->find($fileId);

        if (!$file || $file->storageAccount->user_id !== auth()->id()) {
            return;
        }

        if (!$this->isImage($file->mime_type)) {
            return;
        }

        $this->previewFileId = $fileId;
        $this->showPreviewModal = true;
        $this->dispatch('open-modal', id: 'image-preview-modal'); // ✅ wajib di Filament v3+
    }

    private function isImage(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);
    }

    #[Computed(persist: false)]
    public function previewFile(): ?File
    {
        if (!$this->previewFileId) {
            return null;
        }
        return File::find($this->previewFileId);
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewFileId = null;
    }

    public function goBack(): void
    {
        if (empty($this->breadcrumbs)) {
            return;
        }
        $prev = array_pop($this->breadcrumbs);
        $this->folderId = $prev['id'];
    }

    public function goToRoot(): void
    {
        $this->folderId = null;
        $this->breadcrumbs = [];
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function createFolder(): void
    {
        $this->validate(['newFolderName' => 'required|string|max:255']);
        Folder::create(['name' => $this->newFolderName, 'parent_id' => $this->folderId]);
        $this->newFolderName = '';
        $this->showNewFolderModal = false;
    }

    #[Computed]
    public function folders()
    {
        return Folder::query()->where('parent_id', $this->folderId)->orderBy('name')->get();
    }

    #[Computed]
    public function files()
    {
        return File::query()->where('folder_id', $this->folderId)->orderBy('name')->get();
    }

    #[Computed]
    public function sidebarFolders()
    {
        return Folder::query()->whereNull('parent_id')->orderBy('name')->get();
    }

    #[Computed]
    public function currentFolderName(): string
    {
        if (!$this->folderId) {
            return 'Root';
        }
        return Folder::find($this->folderId)?->name ?? 'Root';
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div>
    {{-- Toolbar --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">

        {{-- ✅ Breadcrumb + Tombol Back --}}
        <div class="flex items-center gap-2">
            @if (!empty($breadcrumbs))
                <button wire:click="goBack"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition"
                    title="Back">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                </button>
            @endif

            {{-- Breadcrumb trail --}}
            <div class="flex items-center gap-1 text-sm text-gray-500">
                <button wire:click="goToRoot"
                    class="hover:text-gray-800 transition {{ is_null($folderId) ? 'text-gray-800 font-medium' : '' }}">
                    Root
                </button>

                @foreach ($breadcrumbs as $crumb)
                    <x-heroicon-o-chevron-right class="w-3 h-3 text-gray-300" />
                    <span class="text-gray-400">{{ $crumb['name'] }}</span>
                @endforeach

                @if ($folderId)
                    <x-heroicon-o-chevron-right class="w-3 h-3 text-gray-300" />
                    <span class="font-medium text-gray-800">{{ $this->currentFolderName }}</span>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            <x-filament::button color="warning" icon="heroicon-o-folder-plus"
                wire:click="$set('showNewFolderModal', true)">
                New Folder
            </x-filament::button>

            <x-filament::button color="warning" icon="heroicon-o-arrow-up-tray" wire:click="$dispatch('open-upload')">
                Upload
            </x-filament::button>

            <x-filament::icon-button icon="heroicon-o-arrow-path" wire:click="$refresh" tooltip="Refresh" />

            <x-filament::icon-button icon="heroicon-o-squares-2x2" wire:click="setView('grid')" :class="$view === 'grid' ? 'bg-gray-100' : ''"
                tooltip="Grid view" />
            <x-filament::icon-button icon="heroicon-o-list-bullet" wire:click="setView('list')" :class="$view === 'list' ? 'bg-gray-100' : ''"
                tooltip="List view" />
        </div>
    </div>

    {{-- Body --}}
    <div class="flex" style="min-height: 500px;">

        {{-- Sidebar --}}
        <div class="w-56 shrink-0 border-r border-gray-200 p-4 overflow-y-auto">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Folders</p>
            <div class="flex flex-col gap-2">
                @foreach ($this->sidebarFolders as $folder)
                    <button wire:click="openFolder({{ $folder->id }})"
                        class="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-sm text-left transition hover:bg-gray-100 {{ $folderId === $folder->id ? 'bg-gray-100 font-medium' : '' }}">
                        <x-heroicon-o-folder class="w-5 h-5 text-amber-400 shrink-0" />
                        {{ $folder->name }}
                    </button>
                @endforeach

            </div>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 p-6 overflow-y-auto">

            @if ($this->folders->isEmpty() && $this->files->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 text-gray-400" style="min-height: 400px;">
                    <x-heroicon-o-folder-open class="w-16 h-16 text-gray-300" />
                    <p class="text-base font-medium text-gray-600">This folder is empty</p>
                    <p class="text-sm">Create a new folder or upload files to get started</p>
                </div>
            @elseif ($view === 'grid')
                {{-- ✅ Grid View — padding lebih lega --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">

                    @foreach ($this->folders as $folder)
                        <button wire:click="openFolder({{ $folder->id }})"
                            class="group flex flex-col items-center gap-3 p-6 rounded-xl border border-gray-200 hover:border-amber-300 hover:bg-amber-50 transition text-center">
                            <x-heroicon-o-folder
                                class="w-12 h-12 text-amber-400 group-hover:text-amber-500 transition" />
                            <span class="text-sm text-gray-600 break-all leading-snug">{{ $folder->name }}</span>
                        </button>
                    @endforeach

                    @foreach ($this->files as $file)
                        <div
                            @if ($file->isImage()) wire:click="openPreview({{ $file->id }})"
            class="group flex flex-col items-center gap-3 p-6 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer relative overflow-hidden"
        @else
            class="flex flex-col items-center gap-3 p-6 rounded-xl border border-gray-200 hover:bg-gray-50 transition text-center cursor-default" @endif>
                            {{-- Thumbnail jika gambar --}}
                            @if ($file->isImage())
                                <div
                                    class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                    <img src="{{ $file->previewUrl() }}" alt="{{ $file->name }}"
                                        class="w-full h-full object-cover" loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                    <div style="display:none" class="w-full h-full items-center justify-center">
                                        <x-heroicon-o-photo class="w-8 h-8 text-blue-300" />
                                    </div>
                                </div>
                            @else
                                <x-dynamic-component :component="$file->icon()" class="w-12 h-12 text-blue-400" />
                            @endif

                            <span class="text-sm text-gray-600 break-all leading-snug">{{ $file->name }}</span>

                            {{-- Badge preview untuk gambar --}}
                            @if ($file->isImage())
                                <span
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full">
                                    Preview
                                </span>
                            @endif
                        </div>
                    @endforeach

                </div>
            @else
                {{-- List View --}}
                <div class="divide-y divide-gray-100">

                    @foreach ($this->folders as $folder)
                        <button wire:click="openFolder({{ $folder->id }})"
                            class="group flex items-center gap-3 w-full px-3 py-3 hover:bg-gray-50 transition text-left">
                            <x-heroicon-o-folder class="w-5 h-5 text-amber-400 shrink-0" />
                            <span class="text-sm text-gray-700">{{ $folder->name }}</span>
                            <x-heroicon-o-chevron-right
                                class="w-4 h-4 text-gray-300 ml-auto group-hover:text-gray-400" />
                        </button>
                    @endforeach

                    @foreach ($this->files as $file)
                        <div
                            @if ($file->isImage()) wire:click="openPreview({{ $file->id }})"
            class="group flex items-center gap-3 px-3 py-3 hover:bg-blue-50 transition cursor-pointer"
        @else
            class="flex items-center gap-3 px-3 py-3 hover:bg-gray-50 transition" @endif>
                            {{-- Thumbnail kecil untuk list view --}}
                            @if ($file->isImage())
                                <div class="w-8 h-8 rounded overflow-hidden bg-gray-100 shrink-0">
                                    <img src="{{ $file->previewUrl() }}" alt="{{ $file->name }}"
                                        class="w-full h-full object-cover" loading="lazy" />
                                </div>
                            @else
                                <x-dynamic-component :component="$file->icon()" class="w-5 h-5 text-blue-400 shrink-0" />
                            @endif

                            <span class="text-sm text-gray-700 flex-1">{{ $file->name }}</span>

                            @if ($file->isImage())
                                <span class="text-xs text-gray-400 group-hover:text-blue-500 transition">Preview</span>
                            @endif
                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>

    {{-- New Folder Modal --}}
    <x-filament::modal id="new-folder-modal" :visible="$showNewFolderModal" width="sm">
        <x-slot name="heading">New Folder</x-slot>

        <x-filament::input.wrapper>
            <x-filament::input type="text" wire:model="newFolderName" placeholder="Folder name"
                wire:keydown.enter="createFolder" autofocus />
        </x-filament::input.wrapper>

        <x-slot name="footerActions">
            <x-filament::button color="gray"
                wire:click="$set('showNewFolderModal', false)">Cancel</x-filament::button>
            <x-filament::button color="warning" wire:click="createFolder">Create</x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Image Preview Modal --}}
    <x-filament::modal id="image-preview-modal" wire:model="showPreviewModal" width="2xl">
        <x-slot name="heading">
            {{ $this->previewFile?->name ?? '' }}
        </x-slot>

        @if ($this->previewFile)
            <div class="flex items-center justify-center bg-gray-50 rounded-lg overflow-hidden"
                style="min-height: 300px; max-height: 70vh;">
                <img src="{{ $this->previewFile->previewUrl() }}" alt="{{ $this->previewFile->name }}"
                    class="max-w-full object-contain" style="max-height: 65vh;" />
            </div>
            <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                <span>{{ strtoupper(last(explode('/', $this->previewFile->mime_type))) }}</span>
                <span>•</span>
                <span>{{ Illuminate\Support\Number::fileSize($this->previewFile->size) }}</span>
            </div>
        @endif

        <x-slot name="footerActions">
            <x-filament::button color="gray" wire:click="closePreview">
                Close
            </x-filament::button>

            @if ($this->previewFile)
                <x-filament::button color="warning" icon="heroicon-o-arrow-top-right-on-square" tag="a"
                    :href="$this->previewFile->previewUrl()" target="_blank">
                    Open Full Size
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>
</div>
