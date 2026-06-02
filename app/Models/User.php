<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
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

    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    public function isSupport(): bool
    {
        return $this->hasRole('support');
    }

    /**
     * Numeric hierarchy level — used for role assignment guards.
     * super_admin(100) > tenant_admin(50) > operator/technician(30) > support/viewer(10)
     */
    public function getHighestRoleLevel(): int
    {
        $levels = [
            'super_admin'  => 100,
            'tenant_admin' => 50,
            'operator'     => 30,
            'technician'   => 30,
            'support'      => 10,
            'viewer'       => 10,
        ];

        $max = 0;
        foreach ($this->roles as $role) {
            $max = max($max, $levels[$role->name] ?? 0);
        }
        return $max;
    }

    /** Human-readable primary role label. */
    public function primaryRoleLabel(): string
    {
        $priority = ['super_admin', 'tenant_admin', 'operator', 'technician', 'support', 'viewer'];
        foreach ($priority as $role) {
            if ($this->hasRole($role)) {
                return match ($role) {
                    'super_admin'  => 'Super Admin',
                    'tenant_admin' => 'Tenant Admin',
                    'operator'     => 'Operator',
                    'technician'   => 'Technician',
                    'support'      => 'Support',
                    'viewer'       => 'Viewer',
                    default        => ucfirst($role),
                };
            }
        }
        return 'No Role';
    }

    /** CSS badge classes for role display. */
    public static function roleBadgeClasses(): array
    {
        return [
            'super_admin'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'tenant_admin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'operator'     => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
            'technician'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'support'      => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'viewer'       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        ];
    }

    /** Audit logs where this user is the subject. */
    public function permissionAuditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PermissionAuditLog::class, 'target_id')
                    ->where('target_type', 'user')
                    ->orderByDesc('created_at');
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
