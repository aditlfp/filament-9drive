<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'parent_id',
        'name',
        'path',
        'color',
        'is_favorite',
        'metadata',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (VirtualFolder $folder) {
            $folder->path = $folder->buildPath();
        });

        static::updating(function (VirtualFolder $folder) {
            if ($folder->isDirty(['parent_id', 'name'])) {
                $folder->path = $folder->buildPath();
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VirtualFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(VirtualFolder::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(VirtualFile::class);
    }

    private function buildPath(): string
    {
        $segments = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($segments, $parent->name);
            $parent = $parent->parent;
        }

        return '/' . implode('/', $segments);
    }

    public static function rootForWorkspace(int $workspaceId): self
    {
        return static::firstOrCreate(
            ['workspace_id' => $workspaceId, 'parent_id' => null, 'name' => 'Root'],
            ['path' => '/Root']
        );
    }
}
