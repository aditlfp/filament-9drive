<?php

namespace App\Filament\Resources\VirtualFiles\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class VirtualFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('virtual_folder_id')
                ->relationship('folder', 'name')
                ->required(),
            // ponytail: direct upload via form; use VirtualFileSystemService in action instead
            FileUpload::make('_upload')->label('File')->disk('local')->visibility('private')->nullable(),
        ]);
    }
}
