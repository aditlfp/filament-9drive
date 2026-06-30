<?php

namespace App\Filament\Resources\VirtualFolders\Schemas;

use App\Models\VirtualFolder;
use App\Models\Workspace;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VirtualFolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('workspace_id')
                ->relationship('workspace', 'name')
                ->required(),
            Select::make('parent_id')
                ->label('Parent folder')
                ->options(fn () => VirtualFolder::query()->pluck('name', 'id'))
                ->nullable()
                ->searchable(),
            TextInput::make('name')->required()->maxLength(255),
        ]);
    }
}
