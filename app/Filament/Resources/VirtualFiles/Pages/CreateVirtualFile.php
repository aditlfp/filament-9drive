<?php

namespace App\Filament\Resources\VirtualFiles\Pages;

use App\Filament\Resources\VirtualFiles\VirtualFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVirtualFile extends CreateRecord
{
    protected static string $resource = VirtualFileResource::class;
}
