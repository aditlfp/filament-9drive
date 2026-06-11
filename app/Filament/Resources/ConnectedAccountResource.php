<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConnectedAccountResource\Pages\ListConnectedAccounts;
use App\Models\ConnectedAccount;
use App\Services\GoogleDriveQuotaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\ProgressBar\Enums\LabelPosition;
use Webkul\ProgressBar\Enums\Shape;
use Webkul\ProgressBar\Enums\Size;
use Webkul\ProgressBar\Tables\Columns\ProgressBar;

class ConnectedAccountResource extends Resource
{
    protected static ?string $model = ConnectedAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Drive Accounts';

    protected static string|\UnitEnum|null $navigationGroup = 'Drive';

    protected static ?string $modelLabel = 'Drive account';

    protected static ?string $pluralModelLabel = 'Drive accounts';

    protected static ?string $recordTitleAttribute = 'google_email';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', auth()->id()))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('google_email')
                    ->label('Google email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Split::make([
                    Stack::make([
                        ProgressBar::make('quota_free')
                            ->value(fn(ConnectedAccount $record): float => $record->quota_used_percentage === null
                                ? 0
                                : max(0, 100 - $record->quota_used_percentage))
                            ->maxValue(100)
                            ->size(Size::Small)
                            ->shape(Shape::Pill)
                            ->labelPosition(LabelPosition::Outside)
                            ->color('success')
                            ->trackColor('gray')
                            ->indeterminate(fn(ConnectedAccount $record): bool => $record->quota_used_percentage === null)
                            ->formatLabel(fn(ConnectedAccount $record): string => $record->quota_used_percentage === null
                                ? 'Quota unknown'
                                : sprintf(
                                    '%s free',
                                    static::formatBytes($record->quota_available),
                                )),
                        ProgressBar::make('quota_used')
                            ->value(fn(ConnectedAccount $record): float => $record->quota_used_percentage ?? 0)
                            ->maxValue(100)
                            ->size(Size::Small)
                            ->shape(Shape::Pill)
                            ->labelPosition(LabelPosition::Outside)
                            ->color('primary')
                            ->trackColor('gray')
                            ->indeterminate(fn(ConnectedAccount $record): bool => $record->quota_used_percentage === null)
                            ->formatLabel(fn(ConnectedAccount $record): string => $record->quota_used_percentage === null
                                ? 'Quota unknown'
                                : sprintf(
                                    '%s%% used',
                                    number_format($record->quota_used_percentage, 2),
                                ))
                    ])
                ]),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last synced')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('connectGoogleDrive')
                    ->label('Connect Google Drive')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn(): string => route('google-drive.connect')),
            ])
            ->recordActions([
                Action::make('refreshQuota')
                    ->label('Refresh quota')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(fn(ConnectedAccount $record, GoogleDriveQuotaService $quotaService) => $quotaService->refresh($record)),
                DeleteAction::make()
                    ->label('Disconnect'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Disconnect selected'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConnectedAccounts::route('/'),
        ];
    }

    protected static function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($index = 0; $bytes >= 1024 && $index < count($units) - 1; $index++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }
}
