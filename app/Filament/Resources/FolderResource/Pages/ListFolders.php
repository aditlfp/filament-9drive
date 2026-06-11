<?php

namespace App\Filament\Resources\FolderResource\Pages;

use App\Filament\Resources\FolderResource;
use App\Models\Folder;
use Filament\Resources\Pages\ListRecords;

class ListFolders extends ListRecords
{
    protected static string $resource = FolderResource::class;

    public function mount(): void
    {
        if (auth()->check()) {
            Folder::rootForUser(auth()->id());
        }

        parent::mount();
    }
}
