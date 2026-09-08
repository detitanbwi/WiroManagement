<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'client_id',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Many-to-Many relationship with roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Cache/list of role slugs for this user.
     */
    public function getRoleSlugs(): array
    {
        if ($this->relationLoaded('roles')) {
            $slugs = $this->roles->pluck('slug')->toArray();
        } else {
            $slugs = $this->roles()->pluck('slug')->toArray();
        }

        // Fallback to legacy role attribute if pivot is empty
        if (empty($slugs) && !empty($this->role)) {
            $slugs[] = $this->role;
        }

        return array_unique($slugs);
    }

    /**
     * Check if user has a specific role or any of the given roles.
     *
     * @param string|UserRole|array $roles
     */
    public function hasRole(string|UserRole|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }

        $slug = $roles instanceof UserRole ? $roles->value : $roles;
        return in_array($slug, $this->getRoleSlugs(), true);
    }

    /**
     * Check if user has at least one of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRoles = $this->getRoleSlugs();

        foreach ($roles as $role) {
            $slug = $role instanceof UserRole ? $role->value : $role;
            if (in_array($slug, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->getRoleSlugs();

        foreach ($roles as $role) {
            $slug = $role instanceof UserRole ? $role->value : $role;
            if (!in_array($slug, $userRoles, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assign one or more roles to the user.
     */
    public function assignRole(string|Role|UserRole ...$roles): self
    {
        foreach ($roles as $role) {
            $slug = $role instanceof Role ? $role->slug : ($role instanceof UserRole ? $role->value : $role);
            $roleModel = Role::where('slug', $slug)->first();
            if ($roleModel) {
                $this->roles()->syncWithoutDetaching([$roleModel->id]);
            }
        }

        $this->syncLegacyRoleColumn();
        return $this;
    }

    /**
     * Sync user roles and update legacy `role` column with the highest-priority role.
     */
    public function syncRoles(array $roles): self
    {
        $slugs = array_map(function ($r) {
            return $r instanceof Role ? $r->slug : ($r instanceof UserRole ? $r->value : $r);
        }, $roles);

        $roleIds = Role::whereIn('slug', $slugs)->pluck('id')->toArray();
        $this->roles()->sync($roleIds);

        $this->syncLegacyRoleColumn($slugs);
        $this->load('roles');

        return $this;
    }

    /**
     * Keep legacy `users.role` column aligned with highest priority role.
     */
    public function syncLegacyRoleColumn(?array $slugs = null): void
    {
        $slugs = $slugs ?? $this->getRoleSlugs();

        $priority = ['superadmin', 'admin', 'pm', 'finance', 'qc', 'staff', 'client'];
        $highestRole = 'staff';

        foreach ($priority as $p) {
            if (in_array($p, $slugs, true)) {
                $highestRole = $p;
                break;
            }
        }

        if ($this->role !== $highestRole) {
            $this->role = $highestRole;
            $this->saveQuietly();
        }
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function isPm(): bool
    {
        return $this->hasRole('pm') || $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function isFinance(): bool
    {
        return $this->hasRole('finance') || $this->isSuperAdmin();
    }

    public function isQc(): bool
    {
        return $this->hasRole('qc') || $this->isSuperAdmin();
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    /**
     * Cache of permission names for the current request cycle.
     */
    protected ?array $cachedPermissionNames = null;

    /**
     * Clear the memoized permissions for this user instance.
     */
    public function clearPermissionCache(): self
    {
        $this->cachedPermissionNames = null;
        return $this;
    }

    /**
     * Get all permission names granted to this user.
     *
     * @return array<string>
     */
    public function getPermissionNames(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        if ($this->cachedPermissionNames === null) {
            $roleIds = $this->roles()->pluck('roles.id')->toArray();

            // Fallback for legacy users.role column if role_user pivot is empty
            if (empty($roleIds) && !empty($this->role)) {
                $roleIds = Role::where('slug', $this->role)->pluck('id')->toArray();
            }

            if (empty($roleIds)) {
                $this->cachedPermissionNames = [];
            } else {
                $this->cachedPermissionNames = \Illuminate\Support\Facades\DB::table('permission_role')
                    ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                    ->whereIn('permission_role.role_id', $roleIds)
                    ->pluck('permissions.name')
                    ->unique()
                    ->values()
                    ->toArray();
            }
        }

        return $this->cachedPermissionNames;
    }

    /**
     * Get all unique permissions granted to this user across all their roles.
     *
     * @return \Illuminate\Support\Collection<int, Permission>
     */
    public function getAllPermissions()
    {
        $permNames = $this->getPermissionNames();
        if (empty($permNames)) {
            return collect();
        }

        return Permission::whereIn('name', $permNames)->get();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permissionName, $this->getPermissionNames(), true);
    }

    /**
     * Check if user has at least one of the specified permissions.
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $allPermissionNames = $this->getPermissionNames();

        foreach ($permissionNames as $perm) {
            if (in_array($perm, $allPermissionNames, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if user has any internal operational role.
     */
    public function isInternal(): bool
    {
        return $this->hasAnyRole(['superadmin', 'admin', 'pm', 'finance', 'qc', 'staff']);
    }

    /**
     * Get array of badge metadata for UI rendering.
     */
    public function getRoleBadgesAttribute(): array
    {
        $badges = [];
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        if ($roles->isEmpty() && !empty($this->role)) {
            $roleModel = Role::where('slug', $this->role)->first();
            if ($roleModel) {
                $roles = collect([$roleModel]);
            }
        }

        foreach ($roles as $role) {
            $enum = UserRole::tryFrom($role->slug);
            $badges[] = [
                'slug' => $role->slug,
                'name' => $enum ? $enum->label() : $role->name,
                'classes' => $enum ? $enum->badgeClasses() : $role->badge_classes,
            ];
        }

        return $badges;
    }
}
