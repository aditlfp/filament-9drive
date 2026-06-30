<?php

namespace App\Providers;

use App\Domain\Contracts\EventBusInterface;
use App\Domain\Contracts\QueueServiceInterface;
use App\Domain\Contracts\SearchServiceInterface;
use App\Domain\Contracts\StorageProviderRegistryInterface;
use App\Domain\Enums\StorageProviderType;
use App\Domain\ValueObjects\ProviderCapabilities;
use App\Infrastructure\Events\SyncEventBus;
use App\Infrastructure\Providers\GoogleDriveProvider;
use App\Infrastructure\Providers\S3CompatibleProvider;
use App\Infrastructure\Providers\StorageProviderFactory;
use App\Infrastructure\Providers\StorageProviderRegistry;
use App\Infrastructure\Queue\LaravelQueueService;
use App\Infrastructure\Search\UnifiedSearchService;
use Illuminate\Support\ServiceProvider;

class StorageProviderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Infrastructure bindings
        $this->app->singleton(QueueServiceInterface::class, LaravelQueueService::class);
        $this->app->singleton(SearchServiceInterface::class, UnifiedSearchService::class);
        $this->app->singleton(EventBusInterface::class, SyncEventBus::class);

        // Registry: metadata + capabilities per provider type
        $this->app->singleton(StorageProviderRegistryInterface::class, function () {
            $registry = new StorageProviderRegistry();

            $registry->register(
                StorageProviderType::GOOGLE_DRIVE,
                GoogleDriveProvider::class,
                ProviderCapabilities::googleDrive()
            );

            $s3Caps = ProviderCapabilities::s3Compatible();

            $registry->register(StorageProviderType::AMAZON_S3, S3CompatibleProvider::class, $s3Caps);
            $registry->register(StorageProviderType::CLOUDFLARE_R2, S3CompatibleProvider::class, $s3Caps);
            $registry->register(StorageProviderType::MINIO, S3CompatibleProvider::class, $s3Caps);

            // ponytail: add DROPBOX, ONEDRIVE, BACKBLAZE_B2, WEBDAV, LOCAL when implemented

            return $registry;
        });

        // Factory: instantiates providers from ConnectedAccount
        $this->app->singleton(StorageProviderFactory::class, function () {
            $factory = new StorageProviderFactory();

            $factory->register(StorageProviderType::GOOGLE_DRIVE, GoogleDriveProvider::class);

            $factory->register(StorageProviderType::AMAZON_S3, S3CompatibleProvider::class);
            $factory->register(StorageProviderType::CLOUDFLARE_R2, S3CompatibleProvider::class);
            $factory->register(StorageProviderType::MINIO, S3CompatibleProvider::class);

            // ponytail: add remaining providers when implemented

            return $factory;
        });
    }
}
