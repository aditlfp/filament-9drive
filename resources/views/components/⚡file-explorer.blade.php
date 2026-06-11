<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\File;
use App\Models\Folder;

class FileExplorer extends Component
{
    public ?int $folderId = null;

    public function openFolder(int $id): void
    {
        $this->folderId = $id;
    }

    public function render()
    {
        return view('livewire.file-explorer', [
            'folders' => Folder::query()->where('parent_id', $this->folderId)->get(),

            'files' => File::query()->where('folder_id', $this->folderId)->get(),
        ]);
    }
}
