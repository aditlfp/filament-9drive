<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Context\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for all workspace-scoped repositories.
 * Subclasses get workspace_id injected automatically.
 */
abstract class WorkspaceScopedRepository
{
    abstract protected function model(): string;

    protected function query(): Builder
    {
        /** @var Model $class */
        $class = $this->model();

        return $class::query()->where('workspace_id', WorkspaceContext::get()->id);
    }

    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }
}
