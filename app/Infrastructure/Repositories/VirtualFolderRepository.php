<?php

namespace App\Infrastructure\Repositories;

use App\Models\VirtualFolder;
use Illuminate\Database\Eloquent\Collection;

class VirtualFolderRepository extends WorkspaceScopedRepository
{
    protected function model(): string
    {
        return VirtualFolder::class;
    }

    public function tree(): Collection
    {
        return $this->query()
            ->whereNull('parent_id')
            ->with('children.children') // two levels eager; ponytail: recursive for deep trees
            ->orderBy('name')
            ->get();
    }

    public function children(int $parentId): Collection
    {
        return $this->query()
            ->where('parent_id', $parentId)
            ->withCount('files')
            ->orderBy('name')
            ->get();
    }

    public function byPath(string $path): ?VirtualFolder
    {
        return $this->query()->where('path', $path)->first();
    }
}
