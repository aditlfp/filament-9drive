<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_email',
        'access_token',
        'refresh_token',
        'expires_at',
        'quota_total',
        'quota_used',
        'quota_refreshed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'quota_total' => 'integer',
            'quota_used' => 'integer',
            'quota_refreshed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'storage_account_id');
    }

    public function getQuotaAvailableAttribute(): ?int
    {
        if ($this->quota_total === null || $this->quota_used === null) {
            return null;
        }

        return max(0, $this->quota_total - $this->quota_used);
    }

    public function getQuotaUsedPercentageAttribute(): ?float
    {
        if (! $this->quota_total) {
            return null;
        }

        return min(100, round(($this->quota_used ?? 0) / $this->quota_total * 100, 2));
    }
}
