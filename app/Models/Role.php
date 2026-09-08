<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'color',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    /**
     * Sync permissions by names or IDs.
     */
    public function syncPermissions(array $permissionIdsOrNames): self
    {
        if (empty($permissionIdsOrNames)) {
            $this->permissions()->sync([]);
            return $this;
        }

        // Check if array contains strings (names) or integers (IDs)
        $isNameArray = is_string(reset($permissionIdsOrNames));

        if ($isNameArray) {
            $permissionIds = Permission::whereIn('name', $permissionIdsOrNames)->pluck('id')->toArray();
        } else {
            $permissionIds = $permissionIdsOrNames;
        }

        $this->permissions()->sync($permissionIds);
        $this->load('permissions');

        return $this;
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->slug === 'superadmin') {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('name', $permissionName);
        }

        return $this->permissions()->where('name', $permissionName)->exists();
    }

    public function getEnumAttribute(): ?UserRole
    {
        return UserRole::tryFrom($this->slug);
    }

    public function getBadgeClassesAttribute(): string
    {
        return match ($this->color) {
            'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
            'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'blue' => 'bg-blue-100 text-blue-800 border-blue-200',
            'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'amber' => 'bg-amber-100 text-amber-800 border-amber-200',
            'rose' => 'bg-rose-100 text-rose-800 border-rose-200',
            'teal' => 'bg-teal-100 text-teal-800 border-teal-200',
            'cyan' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    public function isInternal(): bool
    {
        return $this->slug !== 'client';
    }
}
