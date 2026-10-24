<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectMember extends Model
{
    use HasFactory;

    protected $table = 'project_user';

    protected $fillable = [
        'project_id',
        'user_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'project_user_role', 'project_user_id', 'role_id')
                    ->withTimestamps();
    }

    /**
     * Get slugs of roles for this project member.
     *
     * @return array<string>
     */
    public function getRoleSlugs(): array
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('slug')->toArray();
        }

        return $this->roles()->pluck('slug')->toArray();
    }

    /**
     * Check if member has a specific role or any of the given roles in this project.
     *
     * @param string|array $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }

        return in_array($roles, $this->getRoleSlugs(), true);
    }

    /**
     * Check if member has at least one of the specified roles in this project.
     */
    public function hasAnyRole(array $roles): bool
    {
        $memberRoles = $this->getRoleSlugs();

        foreach ($roles as $role) {
            if (in_array($role, $memberRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync roles for this project membership.
     *
     * @param array<string|Role> $roles
     */
    public function syncRoles(array $roles): self
    {
        $slugs = array_map(function ($r) {
            return $r instanceof Role ? $r->slug : $r;
        }, $roles);

        $roleIds = Role::whereIn('slug', $slugs)->pluck('id')->toArray();
        $this->roles()->sync($roleIds);
        $this->load('roles');

        return $this;
    }

    /**
     * Get badge metadata for UI rendering.
     */
    public function getRoleBadgesAttribute(): array
    {
        $badges = [];
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        foreach ($roles as $role) {
            $badges[] = [
                'slug' => $role->slug,
                'name' => $role->name,
                'classes' => $role->badge_classes,
            ];
        }

        return $badges;
    }
}
