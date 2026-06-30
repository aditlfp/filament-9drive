<?php

namespace App\Filament\Pages;

use App\Domain\Contracts\StorageProviderRegistryInterface;
use App\Domain\Enums\StorageProviderType;
use App\Infrastructure\Context\WorkspaceContext;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Models\ConnectedAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

class StorageAccounts extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;
    protected static ?string $navigationLabel = 'Storage Accounts';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.storage-accounts';

    public function getHeaderActions(): array
    {
        return [
            Action::make('connectAccount')
                ->label('Connect Account')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->form($this->getConnectForm())
                ->action(function (array $data) {
                    $this->connectAccount($data);
                }),
        ];
    }

    protected function getConnectForm(): array
    {
        $registry = app(StorageProviderRegistryInterface::class);
        $providers = collect($registry->available())->mapWithKeys(
            fn (StorageProviderType $type) => [$type->value => $registry->name($type)]
        )->all();

        return [
            Select::make('provider_type')
                ->label('Provider')
                ->options($providers)
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => $set('credentials', [])),

            TextInput::make('account_name')
                ->label('Account Name')
                ->placeholder('e.g., Production S3, Personal Drive')
                ->required()
                ->maxLength(255),

            // Google Drive
            Toggle::make('use_custom_oauth')
                ->label('Use Custom OAuth Credentials')
                ->helperText('By default, built-in OAuth credentials are used. Enable this to provide your own.')
                ->default(false)
                ->reactive()
                ->visible(fn ($get) => $get('provider_type') === StorageProviderType::GOOGLE_DRIVE->value),

            TextInput::make('credentials.client_id')
                ->label('Client ID')
                ->visible(fn ($get) => $get('provider_type') === StorageProviderType::GOOGLE_DRIVE->value && $get('use_custom_oauth'))
                ->required(fn ($get) => $get('provider_type') === StorageProviderType::GOOGLE_DRIVE->value && $get('use_custom_oauth')),

            TextInput::make('credentials.client_secret')
                ->label('Client Secret')
                ->password()
                ->visible(fn ($get) => $get('provider_type') === StorageProviderType::GOOGLE_DRIVE->value && $get('use_custom_oauth'))
                ->required(fn ($get) => $get('provider_type') === StorageProviderType::GOOGLE_DRIVE->value && $get('use_custom_oauth')),

            // S3-compatible
            TextInput::make('credentials.key')
                ->label('Access Key ID')
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true))
                ->required(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),

            TextInput::make('credentials.secret')
                ->label('Secret Access Key')
                ->password()
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true))
                ->required(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),

            TextInput::make('credentials.region')
                ->label('Region')
                ->default('us-east-1')
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),

            TextInput::make('credentials.bucket')
                ->label('Bucket Name')
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true))
                ->required(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::AMAZON_S3->value,
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),

            TextInput::make('credentials.endpoint')
                ->label('Custom Endpoint URL')
                ->url()
                ->placeholder('https://s3.example.com')
                ->helperText('Leave empty for AWS S3. Required for R2, MinIO, and custom S3-compatible providers.')
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),

            Toggle::make('credentials.use_path_style')
                ->label('Use Path-Style Endpoint')
                ->helperText('Enable for MinIO and some S3-compatible providers.')
                ->default(fn ($get) => $get('provider_type') === StorageProviderType::MINIO->value)
                ->visible(fn ($get) => in_array($get('provider_type'), [
                    StorageProviderType::CLOUDFLARE_R2->value,
                    StorageProviderType::MINIO->value,
                ], true)),
        ];
    }

    protected function connectAccount(array $data): void
    {
        $workspace = WorkspaceContext::get();

        // If Google Drive and not using custom OAuth, inject built-in credentials
        $credentials = $data['credentials'] ?? [];
        if ($data['provider_type'] === StorageProviderType::GOOGLE_DRIVE->value && empty($data['use_custom_oauth'])) {
            $credentials['client_id'] = config('services.google.client_id');
            $credentials['client_secret'] = config('services.google.client_secret');
        }

        $account = ConnectedAccount::create([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'provider_type' => $data['provider_type'],
            'account_name' => $data['account_name'],
            'credentials' => $credentials,
            'status' => 'pending',
        ]);

        // Test connection
        try {
            $factory = app(StorageProviderFactory::class);
            $provider = $factory->make($account);

            if ($provider->healthCheck()) {
                $account->update([
                    'status' => 'active',
                    'health_status' => 'healthy',
                    'last_health_check_at' => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title('Account connected')
                    ->body("Successfully connected {$data['account_name']}.")
                    ->send();
            } else {
                $account->update(['status' => 'error', 'health_status' => 'unhealthy']);

                Notification::make()
                    ->warning()
                    ->title('Connection issue')
                    ->body('Account created but health check failed. Please verify credentials.')
                    ->send();
            }
        } catch (\Throwable $e) {
            $account->update(['status' => 'error']);
            Log::error('Storage account connection failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->danger()
                ->title('Connection failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getAccounts()
    {
        $workspace = WorkspaceContext::getOrNull();
        if (! $workspace) return collect();

        return ConnectedAccount::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function testConnection(int $accountId): void
    {
        $account = ConnectedAccount::findOrFail($accountId);

        $ws = WorkspaceContext::getOrNull();
        if (! $ws || $account->workspace_id !== $ws->id) abort(403);

        try {
            $factory = app(StorageProviderFactory::class);
            $provider = $factory->make($account);

            if ($provider->healthCheck()) {
                $account->update([
                    'health_status' => 'healthy',
                    'last_health_check_at' => now(),
                ]);

                Notification::make()->success()->title('Connection test passed')->send();
            } else {
                $account->update(['health_status' => 'unhealthy', 'last_health_check_at' => now()]);
                Notification::make()->warning()->title('Connection test failed')->send();
            }
        } catch (\Throwable $e) {
            $account->update(['health_status' => 'unhealthy', 'last_health_check_at' => now()]);
            Log::error('Connection test failed', ['account_id' => $accountId, 'error' => $e->getMessage()]);

            Notification::make()->danger()->title('Test failed')->body($e->getMessage())->send();
        }
    }

    public function disconnect(int $accountId): void
    {
        $account = ConnectedAccount::findOrFail($accountId);

        $ws = WorkspaceContext::getOrNull();
        if (! $ws || $account->workspace_id !== $ws->id) abort(403);

        $account->delete();

        Notification::make()->success()->title('Account disconnected')->send();
    }
}
