<?php

use App\Models\File;
use App\Models\Folder;
use App\Services\SmartGoogleDriveUploadService;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    // No external listeners needed; modal controlled via wire:model
    public function mount(): void
    {
        // Ensure user has a root folder and start there
        $root = \App\Models\Folder::rootForUser(auth()->id());
        $this->folderId = $root->id;
    }
    public ?int $folderId = null;
    public array $breadcrumbs = []; // ✅ untuk navigasi back
    public string $view = 'grid';
    public bool $showNewFolderModal = false;
    public string $newFolderName = '';
    public ?int $previewFileId = null;
    public bool $showPreviewModal = false;
    public bool $showUploadModal = false;
    public array $uploadedFiles = [];
    public array $selectedFileIds = [];

    public function openFolder(int $folderId): void
    {
        // ✅ simpan history breadcrumb
        $folder = Folder::query()->ownedBy(auth()->id())->find($folderId);

        if (! $folder) {
            return;
        }

        $this->breadcrumbs[] = [
            'id' => $this->folderId,
            'name' => empty($this->breadcrumbs) ? 'Root' : end($this->breadcrumbs)['name'] ?? 'Root',
        ];
        // Sebenarnya kita simpan nama folder yang sedang aktif sebelum pindah
        // Reset & rebuild lebih bersih:
        $this->folderId = $folder->id;
        $this->clearSelection();
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
        $this->clearSelection();
    }

    public function goToRoot(): void
    {
        $root = \App\Models\Folder::rootForUser(auth()->id());
        $this->folderId = $root->id;
        $this->breadcrumbs = [];
        $this->clearSelection();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function openNewFolderModal(): void
    {
        $this->newFolderName = '';
        $this->showNewFolderModal = true;
        $this->dispatch('open-modal', id: 'new-folder-modal');
    }

    public function closeNewFolderModal(): void
    {
        $this->showNewFolderModal = false;
        $this->dispatch('close-modal', id: 'new-folder-modal');
    }

    public function createFolder(): void
    {
        $this->validate(['newFolderName' => 'required|string|max:255']);
        Folder::create(['name' => trim($this->newFolderName), 'parent_id' => $this->folderId, 'user_id' => auth()->id()]);
        unset($this->folders);
        unset($this->sidebarFolders);
        $this->newFolderName = '';
        $this->showNewFolderModal = false;
        $this->dispatch('close-modal', id: 'new-folder-modal');
    }

    public function toggleFileSelection(int $fileId): void
    {
        $file = File::query()
            ->ownedBy(auth()->id())
            ->where('folder_id', $this->folderId)
            ->find($fileId);

        if (! $file) {
            return;
        }

        if (in_array($fileId, $this->selectedFileIds, true)) {
            $this->selectedFileIds = array_values(array_diff($this->selectedFileIds, [$fileId]));

            return;
        }

        $this->selectedFileIds[] = $fileId;
    }

    public function selectAllFilesInCurrentFolder(): void
    {
        $this->selectedFileIds = $this->files->pluck('id')->all();
    }

    public function clearSelection(): void
    {
        $this->selectedFileIds = [];
    }

    #[On('open-upload')]
    public function openUploadModal(): void
    {
        $this->clearUploadedFiles();
        $this->showUploadModal = true;
        $this->dispatch('open-modal', id: 'upload-files-modal');
    }

    public function closeUploadModal(): void
    {
        $this->clearUploadedFiles();
        $this->showUploadModal = false;
        $this->dispatch('close-modal', id: 'upload-files-modal');
    }

    public function clearUploadedFiles(): void
    {
        $this->uploadedFiles = [];
        $this->resetErrorBag('uploadedFiles');
        $this->resetErrorBag('uploadedFiles.*');
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate([
            'uploadedFiles' => ['array'],
            'uploadedFiles.*' => ['file', 'max:' . config('filemanager.upload.max_file_size', 102400)],
        ]);
    }

    public function uploadFiles(SmartGoogleDriveUploadService $uploader): void
    {
        if (empty($this->uploadedFiles)) {
            Notification::make()
                ->title('No files selected')
                ->warning()
                ->send();

            return;
        }

        $folder = Folder::query()->ownedBy(auth()->id())->find($this->folderId)
            ?? Folder::rootForUser(auth()->id());

        $uploadCount = 0;
        $errors = [];

        foreach ($this->uploadedFiles as $uploadedFile) {
            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            try {
                $uploader->uploadUploadedFile($uploadedFile, $folder);
                $uploadCount++;
            } catch (\Throwable $exception) {
                $errors[] = $uploadedFile->getClientOriginalName() . ': ' . $exception->getMessage();
            }
        }

        if ($uploadCount > 0) {
            unset($this->files);
            $this->clearSelection();

            Notification::make()
                ->title($uploadCount . ' file(s) uploaded successfully')
                ->success()
                ->send();

            $this->closeUploadModal();
        }

        if (! empty($errors)) {
            Notification::make()
                ->title('Some files could not be uploaded')
                ->body(implode("\n", array_slice($errors, 0, 5)))
                ->danger()
                ->send();
        }
    }

    #[Computed]
    public function folders()
    {
        return Folder::query()->ownedBy(auth()->id())->where('parent_id', $this->folderId)->orderBy('name')->get();
    }

    #[Computed]
    public function files()
    {
        return File::query()->ownedBy(auth()->id())->where('folder_id', $this->folderId)->orderBy('name')->get();
    }

    #[Computed]
    public function sidebarFolders()
    {
        return Folder::ownedBy(auth()->id())->whereNull('parent_id')->orderBy('name')->get();
    }

    #[Computed]
    public function currentFolderName(): string
    {
        if (!$this->folderId) {
            return 'Root';
        }
        return Folder::query()->ownedBy(auth()->id())->find($this->folderId)?->name ?? 'Root';
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
                wire:click="openNewFolderModal">
                New Folder
            </x-filament::button>

            <x-filament::button color="warning" icon="heroicon-o-arrow-up-tray" wire:click="openUploadModal">
                Upload
            </x-filament::button>

            @if ($this->files->isNotEmpty())
                <x-filament::button color="gray" size="sm" wire:click="selectAllFilesInCurrentFolder">
                    Select all
                </x-filament::button>
            @endif

            @if (count($selectedFileIds) > 0)
                <span class="text-sm text-gray-500">{{ count($selectedFileIds) }} selected</span>
                <form method="POST" action="{{ route('file.download.bulk') }}" class="inline-flex">
                    @csrf
                    @foreach ($selectedFileIds as $selectedFileId)
                        <input type="hidden" name="file_ids[]" value="{{ $selectedFileId }}" />
                    @endforeach
                    <x-filament::button type="submit" color="warning" size="sm" icon="heroicon-o-arrow-down-tray">
                        Download selected
                    </x-filament::button>
                </form>
                <x-filament::button color="gray" size="sm" wire:click="clearSelection">
                    Clear
                </x-filament::button>
            @endif

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
            class="group flex flex-col items-center gap-3 p-6 rounded-xl border border-gray-200 hover:bg-gray-50 transition text-center cursor-default relative" @endif>
                            <button type="button" wire:click.stop="toggleFileSelection({{ $file->id }})"
                                class="absolute top-2 left-2 z-10 inline-flex items-center justify-center w-5 h-5 rounded border bg-white {{ in_array($file->id, $selectedFileIds, true) ? 'border-amber-500 text-amber-600' : 'border-gray-300 text-transparent' }}">
                                <x-heroicon-o-check class="w-3 h-3" />
                            </button>
                            <a href="{{ $file->downloadUrl() }}" x-on:click.stop
                                class="absolute bottom-2 right-2 z-10 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-500 opacity-0 group-hover:opacity-100 hover:text-amber-600 transition"
                                title="Download">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                            </a>

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
            class="group flex items-center gap-3 px-3 py-3 hover:bg-gray-50 transition" @endif>
                            <button type="button" wire:click.stop="toggleFileSelection({{ $file->id }})"
                                class="inline-flex items-center justify-center w-5 h-5 rounded border bg-white {{ in_array($file->id, $selectedFileIds, true) ? 'border-amber-500 text-amber-600' : 'border-gray-300 text-transparent' }}">
                                <x-heroicon-o-check class="w-3 h-3" />
                            </button>

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

                            <a href="{{ $file->downloadUrl() }}" x-on:click.stop
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:text-amber-600 transition"
                                title="Download">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                            </a>
                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>

    {{-- New Folder Modal --}}
    <x-filament::modal id="new-folder-modal" wire:model="showNewFolderModal" width="sm">
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

    {{-- Upload Files Modal --}}
    <x-filament::modal id="upload-files-modal" wire:model="showUploadModal" width="lg">
        <x-slot name="heading">Upload Files</x-slot>

        <x-slot name="description">
            @php
                $maxSizeMB = round(config('filemanager.upload.max_file_size', 102400) / 1024);
            @endphp
            Select one or more files to upload (max {{ $maxSizeMB }}MB per file)
        </x-slot>

        <div class="space-y-4">
            <div x-data="{ isDragging: false }" x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300'">
                <input type="file" x-ref="fileInput" wire:model.live="uploadedFiles" multiple
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />

                <div class="space-y-2" wire:loading.remove wire:target="uploadedFiles">
                    <x-heroicon-o-cloud-arrow-up class="w-12 h-12 mx-auto text-gray-400" />
                    <p class="text-sm text-gray-600">
                        <span class="font-medium text-primary-600">Click to upload</span>
                        or drag and drop
                    </p>
                    <p class="text-xs text-gray-500">Any file type supported</p>
                </div>

                <div class="space-y-2" wire:loading wire:target="uploadedFiles">
                    <div class="w-12 h-12 mx-auto border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-medium text-primary-600">Processing files...</p>
                    <p class="text-xs text-gray-500">Please wait while files are being prepared</p>
                </div>
            </div>

            @error('uploadedFiles.*')
                <p class="text-sm text-danger-600">{{ $message }}</p>
            @enderror

            @if (count($uploadedFiles) > 0)
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700">
                        {{ count($uploadedFiles) }} file(s) ready to upload:
                    </p>
                    <ul class="text-sm text-gray-600 space-y-1 max-h-32 overflow-y-auto">
                        @foreach ($uploadedFiles as $file)
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 shrink-0 text-success-500" />
                                <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-xs text-gray-400">({{ Illuminate\Support\Number::fileSize($file->getSize()) }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="closeUploadModal" color="gray">
                Cancel
            </x-filament::button>
            <x-filament::button wire:click="uploadFiles" wire:loading.attr="disabled"
                wire:target="uploadedFiles, uploadFiles" :disabled="count($uploadedFiles) === 0">
                <span wire:loading.remove wire:target="uploadedFiles, uploadFiles">
                    @if (count($uploadedFiles) > 0)
                        Upload {{ count($uploadedFiles) }} File(s)
                    @else
                        Select Files First
                    @endif
                </span>
                <span wire:loading wire:target="uploadedFiles">Processing...</span>
                <span wire:loading wire:target="uploadFiles">Uploading...</span>
            </x-filament::button>
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
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" tag="a"
                    :href="$this->previewFile->downloadUrl()">
                    Download
                </x-filament::button>
                <x-filament::button color="warning" icon="heroicon-o-arrow-top-right-on-square" tag="a"
                    :href="$this->previewFile->previewUrl()" target="_blank">
                    Open Full Size
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>
</div>
