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
        'workspace_id',
        'provider_type',
        'account_name',
        'google_email',
        'access_token',
        'refresh_token',
        'credentials',
        'expires_at',
        'quota_total',
        'quota_used',
        'quota_refreshed_at',
        'last_health_check_at',
        'health_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'credentials' => 'encrypted:array',
            'expires_at' => 'datetime',
            'quota_total' => 'integer',
            'quota_used' => 'integer',
            'quota_refreshed_at' => 'datetime',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'storage_account_id');
    }

    public function virtualFiles(): HasMany
    {
        return $this->hasMany(VirtualFile::class, 'connected_account_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isHealthy(): bool
    {
        return $this->health_status === 'healthy';
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
