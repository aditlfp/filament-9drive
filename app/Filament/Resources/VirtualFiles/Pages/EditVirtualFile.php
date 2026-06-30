<?php

namespace App\Filament\Resources\VirtualFiles\Pages;

use App\Filament\Resources\VirtualFiles\VirtualFileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVirtualFile extends EditRecord
{
    protected static string $resource = VirtualFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
