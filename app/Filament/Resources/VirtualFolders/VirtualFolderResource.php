<?php

namespace App\Filament\Resources\VirtualFolders;

use App\Filament\Resources\VirtualFolders\Pages\CreateVirtualFolder;
use App\Filament\Resources\VirtualFolders\Pages\EditVirtualFolder;
use App\Filament\Resources\VirtualFolders\Pages\ListVirtualFolders;
use App\Filament\Resources\VirtualFolders\Schemas\VirtualFolderForm;
use App\Filament\Resources\VirtualFolders\Tables\VirtualFoldersTable;
use App\Models\VirtualFolder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VirtualFolderResource extends Resource
{
    protected static ?string $model = VirtualFolder::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;
    protected static ?string $navigationLabel = 'Folders';
    protected static string|\UnitEnum|null $navigationGroup = 'Drive';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return VirtualFolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VirtualFoldersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVirtualFolders::route('/'),
            'create' => CreateVirtualFolder::route('/create'),
            'edit' => EditVirtualFolder::route('/{record}/edit'),
        ];
    }
}
