<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Role helpers ──────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isTenantAdmin(): bool
    {
        return $this->hasRole('tenant_admin');
    }

    public function isViewer(): bool
    {
        return $this->hasRole('viewer');
    }

    public function isTechnician(): bool
    {
        return $this->hasRole('technician');
    }

    // ── Filament ──────────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // ── Company access control ────────────────────────────────────

    /**
     * Return all company IDs this user may access.
     *
     * Super admin  → every company in the system
     * Tenant user  → own company + all descendant companies (reseller tree)
     * No company   → empty (should not normally happen for non-super admins)
     *
     * Tip: call `$user->load('company.childCompanies')` before using this
     * in a loop to prevent N+1 queries.
     */
    public function accessibleCompanyIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Company::pluck('id')->toArray();
        }

        if ($this->company_id === null) {
            return [];
        }

        // Lazy-load so single calls are still efficient
        if (! $this->relationLoaded('company')) {
            $this->load('company');
        }

        return $this->company->descendantIds();
    }

    /**
     * Check whether this user may access data belonging to $companyId.
     * Respects the full reseller hierarchy.
     */
    public function canAccessCompany(int $companyId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($companyId, $this->accessibleCompanyIds(), true);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
