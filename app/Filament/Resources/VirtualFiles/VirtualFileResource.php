<?php

namespace App\Filament\Resources\VirtualFiles;

use App\Filament\Resources\VirtualFiles\Pages\CreateVirtualFile;
use App\Filament\Resources\VirtualFiles\Pages\EditVirtualFile;
use App\Filament\Resources\VirtualFiles\Pages\ListVirtualFiles;
use App\Filament\Resources\VirtualFiles\Schemas\VirtualFileForm;
use App\Filament\Resources\VirtualFiles\Tables\VirtualFilesTable;
use App\Models\VirtualFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VirtualFileResource extends Resource
{
    protected static ?string $model = VirtualFile::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;
    protected static ?string $navigationLabel = 'Files';
    protected static string|\UnitEnum|null $navigationGroup = 'Drive';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return VirtualFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VirtualFilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVirtualFiles::route('/'),
            'create' => CreateVirtualFile::route('/create'),
            'edit' => EditVirtualFile::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
