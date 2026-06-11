<?php

namespace App\Filament\Widgets;

use App\Models\ConnectedAccount;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DriveAccountStorageWidget extends TableWidget
{
    protected static ?int $sort = 20;

    protected int | string | array $columnSpan = 'full';

    public function getLabel(): string
    {
        return 'Drive account storage';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Drive account storage')
            ->description('Per-account quota details used by smart upload selection.')
            ->query(fn (): Builder => ConnectedAccount::query()
                ->where('user_id', auth()->id())
                ->latest('updated_at'))
            ->columns([
                TextColumn::make('google_email')
                    ->label('Account')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('quota_total')
                    ->label('Total')
                    ->state(fn (ConnectedAccount $record): string => self::formatNullableBytes($record->quota_total)),
                TextColumn::make('quota_used')
                    ->label('Used')
                    ->state(fn (ConnectedAccount $record): string => self::formatNullableBytes($record->quota_used)),
                TextColumn::make('quota_available')
                    ->label('Free')
                    ->state(fn (ConnectedAccount $record): string => self::formatNullableBytes($record->quota_available)),
                TextColumn::make('quota_used_percentage')
                    ->label('Usage')
                    ->state(fn (ConnectedAccount $record): string => $record->quota_used_percentage === null
                        ? 'Unknown'
                        : number_format($record->quota_used_percentage, 2).'%')
                    ->badge()
                    ->color(fn (ConnectedAccount $record): string => match (true) {
                        $record->quota_used_percentage === null => 'gray',
                        $record->quota_used_percentage >= 90 => 'danger',
                        $record->quota_used_percentage >= 75 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('quota_refreshed_at')
                    ->label('Refreshed')
                    ->since()
                    ->placeholder('Never'),
            ])
            ->paginated([5, 10]);
    }

    private static function formatNullableBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($index = 0; $bytes >= 1024 && $index < count($units) - 1; $index++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
