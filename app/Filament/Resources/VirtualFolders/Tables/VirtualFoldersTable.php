<?php

namespace App\Filament\Resources\VirtualFolders\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VirtualFoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('path')->label('Path')->searchable(),
                TextColumn::make('workspace.name')->label('Workspace')->sortable(),
                TextColumn::make('files_count')->counts('files')->label('Files'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
