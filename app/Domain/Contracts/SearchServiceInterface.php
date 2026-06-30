<?php

namespace App\Domain\Contracts;

use App\Domain\Entities\FileEntity;
use App\Models\VirtualFile;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unified search across all connected providers.
 * Implementation fans out to providers in parallel.
 */
interface SearchServiceInterface
{
    /**
     * Search local metadata (fast).
     */
    public function local(Workspace $workspace, string $query): Collection;

    /**
     * Search all connected providers (async) + local.
     * Returns a fresh Collection; remote results are merged.
     */
    public function everywhere(Workspace $workspace, string $query): Collection;

    /**
     * Search a specific provider only.
     */
    public function provider(Workspace $workspace, string $providerType, string $query): Collection;
}
