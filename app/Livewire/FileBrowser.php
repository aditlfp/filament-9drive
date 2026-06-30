<?php

namespace App\Livewire;

use App\Application\DTOs\UploadContext;
use App\Application\Services\SharingService;
use App\Application\Services\VirtualFileSystemService;
use App\Domain\Enums\Permission;
use App\Infrastructure\Auth\WorkspaceGate;
use App\Infrastructure\Context\WorkspaceContext;
use App\Infrastructure\Repositories\VirtualFileRepository;
use App\Infrastructure\Repositories\VirtualFolderRepository;
use App\Models\VirtualFile;
use App\Models\VirtualFolder;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileBrowser extends Component
{
    use WithFileUploads;

    public ?int $folderId = null;
    public array $breadcrumbs = [];
    public string $view = 'grid';
    public array $selectedFileIds = [];
    public array $uploadQueue = [];
    public bool $showUploadModal = false;
    public bool $showNewFolderModal = false;
    public string $newFolderName = '';
    public ?int $previewFileId = null;
    public bool $showPreviewModal = false;

    public function mount(): void
    {
        WorkspaceGate::authorize(Permission::ViewFiles);

        $workspace = WorkspaceContext::get();
        $root = VirtualFolder::rootForWorkspace($workspace->id);
        $this->folderId = $root->id;
    }

    #[Computed]
    public function folders()
    {
        $repo = app(VirtualFolderRepository::class);
        return $repo->children($this->folderId);
    }

    #[Computed]
    public function files()
    {
        $repo = app(VirtualFileRepository::class);
        return $repo->inFolder($this->folderId);
    }

    #[Computed]
    public function currentFolder()
    {
        return VirtualFolder::find($this->folderId);
    }

    #[Computed]
    public function sidebarTree()
    {
        $repo = app(VirtualFolderRepository::class);
        return $repo->tree();
    }

    public function openFolder(int $id): void
    {
        $folder = VirtualFolder::find($id);
        if (! $folder || $folder->workspace_id !== WorkspaceContext::get()->id) {
            return;
        }

        $this->breadcrumbs[] = [
            'id' => $this->folderId,
            'name' => $this->currentFolder->name,
        ];

        $this->folderId = $id;
        $this->clearSelection();
        unset($this->folders, $this->files);
    }

    public function goBack(): void
    {
        if (empty($this->breadcrumbs)) {
            return;
        }

        $prev = array_pop($this->breadcrumbs);
        $this->folderId = $prev['id'];
        unset($this->folders, $this->files);
    }

    public function goToRoot(): void
    {
        $workspace = WorkspaceContext::get();
        $root = VirtualFolder::rootForWorkspace($workspace->id);
        $this->folderId = $root->id;
        $this->breadcrumbs = [];
        unset($this->folders, $this->files);
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function toggleSelection(int $fileId): void
    {
        if (in_array($fileId, $this->selectedFileIds, true)) {
            $this->selectedFileIds = array_values(array_diff($this->selectedFileIds, [$fileId]));
        } else {
            $this->selectedFileIds[] = $fileId;
        }
    }

    public function selectAll(): void
    {
        $this->selectedFileIds = $this->files->pluck('id')->all();
    }

    public function clearSelection(): void
    {
        $this->selectedFileIds = [];
    }

    public function openNewFolderModal(): void
    {
        WorkspaceGate::authorize(Permission::CreateFolders);
        $this->newFolderName = '';
        $this->showNewFolderModal = true;
    }

    public function createFolder(VirtualFileSystemService $fs): void
    {
        WorkspaceGate::authorize(Permission::CreateFolders);
        $this->validate(['newFolderName' => 'required|string|max:255']);

        $workspace = WorkspaceContext::get();
        $parent = VirtualFolder::find($this->folderId);

        $fs->createFolder($workspace, trim($this->newFolderName), $parent);

        unset($this->folders);
        $this->showNewFolderModal = false;

        Notification::make()->success()->title('Folder created')->send();
    }

    public function openUploadModal(): void
    {
        WorkspaceGate::authorize(Permission::UploadFiles);
        $this->uploadQueue = [];
        $this->showUploadModal = true;
    }

    public function updatedUploadQueue(): void
    {
        $this->validate([
            'uploadQueue' => 'array',
            'uploadQueue.*' => 'file|max:512000', // 500MB
        ]);
    }

    public function uploadFiles(VirtualFileSystemService $fs): void
    {
        WorkspaceGate::authorize(Permission::UploadFiles);

        if (empty($this->uploadQueue)) {
            Notification::make()->warning()->title('No files selected')->send();
            return;
        }

        $workspace = WorkspaceContext::get();
        $folder = VirtualFolder::findOrFail($this->folderId);
        $uploaded = 0;
        $errors = [];

        foreach ($this->uploadQueue as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $fs->upload($file, $folder, $workspace);
                $uploaded++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        unset($this->files);
        $this->uploadQueue = [];
        $this->showUploadModal = false;

        if ($uploaded > 0) {
            Notification::make()->success()->title("{$uploaded} file(s) uploaded")->send();
        }

        if (! empty($errors)) {
            Notification::make()->danger()->title('Upload errors')->body(implode("\n", array_slice($errors, 0, 3)))->send();
        }
    }

    public function deleteSelected(VirtualFileSystemService $fs): void
    {
        WorkspaceGate::authorize(Permission::DeleteFiles);

        $deleted = 0;
        foreach (VirtualFile::whereIn('id', $this->selectedFileIds)->get() as $file) {
            if ($file->workspace_id === WorkspaceContext::get()->id) {
                $fs->delete($file);
                $deleted++;
            }
        }

        unset($this->files);
        $this->clearSelection();

        Notification::make()->success()->title("{$deleted} file(s) deleted")->send();
    }

    public function toggleFavorite(int $fileId): void
    {
        $file = VirtualFile::find($fileId);
        if ($file && $file->workspace_id === WorkspaceContext::get()->id) {
            $file->update(['is_favorite' => ! $file->is_favorite]);
            unset($this->files);
        }
    }

    public function createShare(int $fileId): void
    {
        $file = VirtualFile::find($fileId);
        if (! $file || $file->workspace_id !== WorkspaceContext::get()->id) return;

        $sharing = app(SharingService::class);
        $share = $sharing->createShare($file);
        Notification::make()->success()->title('Share created')->body($share->getPublicUrl())->send();
    }

    public function openPreview(int $fileId): void
    {
        $file = VirtualFile::find($fileId);
        if (! $file || $file->workspace_id !== WorkspaceContext::get()->id) {
            return;
        }

        $this->previewFileId = $fileId;
        $this->showPreviewModal = true;
    }

    #[Computed]
    public function previewFile()
    {
        return $this->previewFileId ? VirtualFile::find($this->previewFileId) : null;
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewFileId = null;
    }

    public function fileIconColor(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'text-blue-500',
            str_starts_with($mimeType, 'video/') => 'text-purple-500',
            str_starts_with($mimeType, 'audio/') => 'text-pink-500',
            str_contains($mimeType, 'pdf') => 'text-red-500',
            str_contains($mimeType, 'zip') || str_contains($mimeType, 'archive') => 'text-amber-500',
            str_contains($mimeType, 'text') || str_contains($mimeType, 'json') => 'text-gray-500',
            default => 'text-gray-400',
        };
    }

    public function render()
    {
        return view('livewire.file-browser');
    }
}
