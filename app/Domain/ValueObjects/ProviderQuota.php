<?php

namespace App\Domain\ValueObjects;

readonly class ProviderQuota
{
    public function __construct(
        public ?int $total,
        public ?int $used,
        public ?int $available,
    ) {}

    public function usagePercentage(): ?float
    {
        if ($this->total === null || $this->used === null || $this->total === 0) {
            return null;
        }

        return ($this->used / $this->total) * 100;
    }

    public function isUnlimited(): bool
    {
        return $this->total === null;
    }
}
