<?php

namespace App\Infrastructure\Repositories;

use App\Models\VirtualFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VirtualFileRepository extends WorkspaceScopedRepository
{
    protected function model(): string
    {
        return VirtualFile::class;
    }

    public function inFolder(int $folderId): Collection
    {
        return $this->query()
            ->where('virtual_folder_id', $folderId)
            ->with('account')
            ->orderBy('name')
            ->get();
    }

    public function paginate(int $folderId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->query()
            ->where('virtual_folder_id', $folderId)
            ->with('account')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function search(string $term): Collection
    {
        return $this->query()
            ->where('name', 'like', "%{$term}%")
            ->with(['folder', 'account'])
            ->limit(100)
            ->get();
    }

    public function favorites(): Collection
    {
        return $this->query()
            ->where('is_favorite', true)
            ->with(['folder', 'account'])
            ->orderBy('name')
            ->get();
    }
}
