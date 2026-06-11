<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FolderResource\Pages\ListFolders;
use App\Models\Folder;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Drive';

    protected static ?string $navigationLabel = 'Virtual Folders';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn (): ?int => auth()->id()),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('parent_id')
                ->label('Parent folder')
                ->options(fn (): array => Folder::optionsForUser(auth()->id()))
                ->searchable()
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->ownedBy(auth()->id())->with('parent'))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('path')
                    ->label('Virtual path')
                    ->state(fn (Folder $record): string => $record->path)
                    ->searchable(['name']),
                TextColumn::make('files_count')
                    ->counts('files')
                    ->label('Files'),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Folder $record): bool => $record->parent_id !== null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFolders::route('/'),
        ];
    }
}
