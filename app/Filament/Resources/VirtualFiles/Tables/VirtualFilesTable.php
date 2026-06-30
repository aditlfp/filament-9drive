<?php

namespace App\Filament\Resources\VirtualFiles\Tables;

use App\Models\VirtualFile;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VirtualFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('folder.name')->label('Folder')->sortable(),
                TextColumn::make('account.google_email')->label('Stored on')->toggleable(),
                TextColumn::make('formatted_size')->label('Size'),
                TextColumn::make('mime_type')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_favorite')
                    ->boolean()
                    ->label('★'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
