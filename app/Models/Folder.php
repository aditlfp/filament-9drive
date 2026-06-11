<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'user_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Folder $folder): bool => Cache::forget(self::optionsCacheKey($folder->user_id)));
        static::deleted(fn (Folder $folder): bool => Cache::forget(self::optionsCacheKey($folder->user_id)));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public static function rootForUser(User|int $user): self
    {
        $userId = $user instanceof User ? $user->id : $user;

        return self::query()->firstOrCreate([
            'user_id' => $userId,
            'parent_id' => null,
            'name' => 'Root',
        ]);
    }

    public function scopeOwnedBy(Builder $query, User|int|null $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function getPathAttribute(): string
    {
        $segments = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($segments, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $segments);
    }

    public static function optionsForUser(User|int|null $user): array
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (! $userId) {
            return [];
        }

        return Cache::remember(self::optionsCacheKey($userId), now()->addMinutes(10), function () use ($userId): array {
            $folders = self::query()
                ->ownedBy($userId)
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name']);

            $byParent = $folders->groupBy(fn (Folder $folder): int => $folder->parent_id ?? 0);
            $paths = [];

            $build = function (?int $parentId, string $prefix = '') use (&$build, $byParent, &$paths): void {
                foreach ($byParent->get($parentId ?? 0, collect()) as $folder) {
                    $path = $prefix === '' ? $folder->name : "{$prefix} / {$folder->name}";
                    $paths[$folder->id] = $path;
                    $build($folder->id, $path);
                }
            };

            $build(null);

            return $paths;
        });
    }

    protected static function optionsCacheKey(int $userId): string
    {
        return "folders.options.{$userId}";
    }
}
