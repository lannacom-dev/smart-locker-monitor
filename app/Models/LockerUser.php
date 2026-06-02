<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerUser extends Model
{
    protected $fillable = [
        'company_id',
        'user_type_id',
        'created_by',
        'updated_by',
        'full_name',
        'email',
        'phone',
        'employee_id',
        'organization',
        'is_active',
        'access_start_date',
        'access_end_date',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'access_start_date' => 'date',
        'access_end_date'   => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfType(Builder $query, string $typeSlug): Builder
    {
        return $query->whereHas('userType', fn (Builder $q) => $q->where('slug', $typeSlug));
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** True when the access window is currently valid (or has no restriction). */
    public function hasActiveAccess(): bool
    {
        if (! $this->is_active) return false;

        $today = now()->toDateString();

        if ($this->access_start_date && $this->access_start_date->toDateString() > $today) {
            return false;
        }

        if ($this->access_end_date && $this->access_end_date->toDateString() < $today) {
            return false;
        }

        return true;
    }

    /** Formatted access window for display. */
    public function accessWindowLabel(): string
    {
        if (! $this->access_start_date && ! $this->access_end_date) {
            return 'No restriction';
        }

        $start = $this->access_start_date?->format('d M Y') ?? '—';
        $end   = $this->access_end_date?->format('d M Y')   ?? '—';

        return "{$start} – {$end}";
    }
}
