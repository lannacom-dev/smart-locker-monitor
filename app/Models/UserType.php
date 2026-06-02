<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    // ── Type slug constants ───────────────────────────────────────

    const SLUG_EMPLOYEE      = 'employee';
    const SLUG_VISITOR       = 'visitor';
    const SLUG_DELIVERY      = 'delivery';
    const SLUG_TENANT_USER   = 'tenant_user';
    const SLUG_EXTERNAL_USER = 'external_user';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lockerUsers(): HasMany
    {
        return $this->hasMany(LockerUser::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemTypes(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to types visible to a given actor:
     * - system types (company_id = null) are visible to everyone
     * - tenant-specific types are visible only within the actor's company tree
     */
    public function scopeForActor(Builder $query, User $actor): Builder
    {
        if ($actor->isSuperAdmin()) {
            return $query;
        }

        $ids = $actor->accessibleCompanyIds();

        return $query->where(function (Builder $q) use ($ids) {
            $q->where('is_system', true)
              ->orWhereIn('company_id', $ids);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Tailwind CSS badge classes keyed by slug. */
    public static function badgeClasses(): array
    {
        return [
            self::SLUG_EMPLOYEE      => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            self::SLUG_VISITOR       => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::SLUG_DELIVERY      => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            self::SLUG_TENANT_USER   => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            self::SLUG_EXTERNAL_USER => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        ];
    }

    /** Human-readable label for the slug. */
    public function scopeLabel(): string
    {
        return match ($this->slug) {
            self::SLUG_EMPLOYEE      => 'Employee',
            self::SLUG_VISITOR       => 'Visitor',
            self::SLUG_DELIVERY      => 'Delivery',
            self::SLUG_TENANT_USER   => 'Tenant User',
            self::SLUG_EXTERNAL_USER => 'External User',
            default                  => ucfirst(str_replace('_', ' ', $this->slug)),
        };
    }

    /** True when this type requires the employee_id field. */
    public function requiresEmployeeId(): bool
    {
        return $this->slug === self::SLUG_EMPLOYEE;
    }

    /** True when this type requires the organization field. */
    public function requiresOrganization(): bool
    {
        return in_array($this->slug, [self::SLUG_DELIVERY, self::SLUG_EXTERNAL_USER], true);
    }
}
