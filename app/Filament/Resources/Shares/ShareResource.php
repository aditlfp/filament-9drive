<?php

namespace App\Filament\Resources\Shares;

use App\Filament\Resources\Shares\Pages\CreateShare;
use App\Filament\Resources\Shares\Pages\EditShare;
use App\Filament\Resources\Shares\Pages\ListShares;
use App\Infrastructure\Context\WorkspaceContext;
use App\Models\Share;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Radio;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShareResource extends Resource
{
    protected static ?string $model = Share::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;
    protected static ?string $navigationLabel = 'Shares';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('virtual_file_id')
                ->label('File')
                ->relationship('file', 'name', fn($q) => WorkspaceContext::has() ? $q->where('workspace_id', WorkspaceContext::get()->id) : $q)
                ->searchable()
                ->preload()
                ->required(),

            Radio::make('type')
                ->options([
                    'public' => 'Public',
                    'password' => 'Password Protected',
                    'workspace_only' => 'Workspace Only',
                ])
                ->default('public')
                ->inline()
                ->required(),

            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => $state ? \Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->visible(fn ($get) => $get('type') === 'password'),

            DateTimePicker::make('expires_at')
                ->label('Expiration Date')
                ->native(false)
                ->seconds(false),

            TextInput::make('download_limit')
                ->label('Download Limit')
                ->numeric()
                ->minValue(1)
                ->suffix('downloads'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => WorkspaceContext::has() ? $q->where('workspace_id', WorkspaceContext::get()->id) : $q->whereRaw('1 = 0'))
            ->columns([
                TextColumn::make('resourceName')
                    ->label('Resource')
                    ->searchable(['virtual_files.name', 'virtual_folders.name'])
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'public' => 'success',
                        'password' => 'warning',
                        'workspace_only' => 'info',
                    }),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->formatStateUsing(fn ($record) => $record->download_limit
                        ? "{$record->download_count} / {$record->download_limit}"
                        : (string) $record->download_count
                    ),

                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                TextColumn::make('last_accessed_at')
                    ->label('Last Access')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('copyLink')
                    ->icon('heroicon-o-clipboard')
                    ->color('success')
                    ->action(fn ($record) => null)
                    ->extraAttributes(fn ($record) => [
                        'x-data' => '{}',
                        'x-on:click' => "navigator.clipboard.writeText('{$record->getPublicUrl()}'); \$tooltip('Copied!', { timeout: 2000 })",
                    ]),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShares::route('/'),
            'create' => CreateShare::route('/create'),
            'edit' => EditShare::route('/{record}/edit'),
        ];
    }
}
