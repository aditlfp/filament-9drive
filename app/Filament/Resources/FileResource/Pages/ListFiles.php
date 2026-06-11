<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use App\Models\Folder;
use Filament\Resources\Pages\ListRecords;

class ListFiles extends ListRecords
{
    protected static string $resource = FileResource::class;

    public function mount(): void
    {
        if (auth()->check()) {
            Folder::rootForUser(auth()->id());
        }

        parent::mount();
    }
}
