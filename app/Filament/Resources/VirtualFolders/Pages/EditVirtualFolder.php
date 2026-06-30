<?php

namespace App\Filament\Resources\VirtualFolders\Pages;

use App\Filament\Resources\VirtualFolders\VirtualFolderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVirtualFolder extends EditRecord
{
    protected static string $resource = VirtualFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
