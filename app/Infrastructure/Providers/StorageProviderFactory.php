<?php

namespace App\Infrastructure\Providers;

use App\Domain\Contracts\StorageProviderInterface;
use App\Domain\Enums\StorageProviderType;
use App\Models\ConnectedAccount;
use InvalidArgumentException;

class StorageProviderFactory
{
    private array $providers = [];

    public function register(StorageProviderType $type, string $class): void
    {
        if (!is_subclass_of($class, StorageProviderInterface::class)) {
            throw new InvalidArgumentException(
                "Provider {$class} must implement StorageProviderInterface"
            );
        }

        $this->providers[$type->value] = $class;
    }

    public function make(ConnectedAccount $account): StorageProviderInterface
    {
        $type = StorageProviderType::from($account->provider_type);

        if (!isset($this->providers[$type->value])) {
            throw new InvalidArgumentException(
                "No provider registered for type: {$type->value}"
            );
        }

        $class = $this->providers[$type->value];

        return app($class, ['account' => $account]);
    }

    public function supports(StorageProviderType $type): bool
    {
        return isset($this->providers[$type->value]);
    }

    public function available(): array
    {
        return array_keys($this->providers);
    }
}
