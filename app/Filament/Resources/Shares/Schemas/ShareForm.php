<?php

namespace App\Filament\Resources\Shares\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('workspace_id')
                    ->relationship('workspace', 'name')
                    ->required(),
                TextInput::make('virtual_file_id')
                    ->numeric(),
                TextInput::make('virtual_folder_id')
                    ->numeric(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('token')
                    ->required(),
                Select::make('type')
                    ->options(['public' => 'Public', 'password' => 'Password', 'workspace_only' => 'Workspace only'])
                    ->default('public')
                    ->required(),
                TextInput::make('password_hash')
                    ->password(),
                DateTimePicker::make('expires_at'),
                TextInput::make('download_limit')
                    ->numeric(),
                TextInput::make('download_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('last_accessed_at'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('metadata'),
            ]);
    }
}
