<?php

namespace App\Infrastructure\Queue;

use App\Domain\Contracts\QueueServiceInterface;
use App\Infrastructure\Context\WorkspaceContext;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class LaravelQueueService implements QueueServiceInterface
{
    public function push(object $job, ?string $queue = null): mixed
    {
        return $queue
            ? Bus::dispatchOn($queue, $job)
            : Bus::dispatch($job);
    }

    public function batch(array $jobs, string $name): ?Batch
    {
        if (! class_exists(Bus::class)) {
            return null;
        }

        return Bus::batch($jobs)->name($name)->dispatch();
    }

    public function afterResponse(object $job): void
    {
        Bus::dispatchAfterResponse($job);
    }
}
