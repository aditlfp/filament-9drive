<?php

namespace App\Filament\Resources\VirtualFolders\Pages;

use App\Filament\Resources\VirtualFolders\VirtualFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVirtualFolders extends ListRecords
{
    protected static string $resource = VirtualFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
