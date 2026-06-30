<?php

namespace App\Filament\Resources\VirtualFiles\Pages;

use App\Filament\Resources\VirtualFiles\VirtualFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVirtualFiles extends ListRecords
{
    protected static string $resource = VirtualFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
