<?php

namespace App\Http\Controllers;

use App\Models\ManHourRate;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMemberRate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    /**
     * Assign a user to the project team with functional position, agreed rate, and optional RBAC roles.
     */
    public function store(Request $request, Project $project)
    {
        abort_unless(auth()->user()->can('projects.manage') || auth()->user()->isSuperAdmin(), 403, 'Akses ditolak.');

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($project) {
                    if ($project->hasMember($value)) {
                        $fail('Pengguna ini sudah terdaftar dalam tim proyek.');
                    }
                },
            ],
            'role_in_project' => 'nullable|string|max:100',
            'rate_per_hour' => 'nullable|numeric|min:0',
            'weekend_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'overtime_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'notes' => 'nullable|string|max:500',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:50',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $roles = !empty($validated['roles']) ? $validated['roles'] : ['staff'];
        $roleInProject = $validated['role_in_project'] ?? null;
        $ratePerHour = isset($validated['rate_per_hour']) && $validated['rate_per_hour'] !== '' ? (float)$validated['rate_per_hour'] : null;

        // Auto-fill rate from master rate card if not explicitly set
        if (($ratePerHour === null || $ratePerHour <= 0) && $roleInProject) {
            $masterRate = ManHourRate::where('role_slug', $roleInProject)
                ->orWhere('role_slug', strtolower(str_replace(' ', '_', $roleInProject)))
                ->first();
            if ($masterRate) {
                $ratePerHour = (float)$masterRate->base_rate_per_hour;
            }
        }

        $member = $project->assignMember(
            user: $user,
            roles: $roles,
            roleInProject: $roleInProject,
            ratePerHour: $ratePerHour,
            weekendMultiplier: $validated['weekend_multiplier'] ?? null,
            overtimeMultiplier: $validated['overtime_multiplier'] ?? null
        );

        $user->clearPermissionCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pengguna {$user->name} berhasil ditugaskan ke proyek.",
                'member' => $member->load('roles', 'user', 'memberRate'),
            ]);
        }

        return back()->with('success', "Anggota tim {$user->name} berhasil ditugaskan ke proyek sebagai {$member->display_position}.");
    }

    /**
     * Update position, rate, and roles for an assigned project member.
     */
    public function update(Request $request, Project $project, ProjectMember $member)
    {
        abort_unless(auth()->user()->can('projects.manage') || auth()->user()->isSuperAdmin(), 403, 'Akses ditolak.');

        // Ensure member belongs to this project
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'role_in_project' => 'nullable|string|max:100',
            'rate_per_hour' => 'nullable|numeric|min:0',
            'weekend_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'overtime_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'notes' => 'nullable|string|max:500',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:50',
        ]);

        if (array_key_exists('role_in_project', $validated)) {
            $member->role_in_project = $validated['role_in_project'];
            $member->save();
        }

        if (isset($validated['roles'])) {
            $member->syncRoles($validated['roles']);
        }

        if (isset($validated['rate_per_hour']) && $validated['rate_per_hour'] !== '') {
            ProjectMemberRate::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $member->user_id],
                [
                    'role_slug' => $validated['role_in_project'] ?? $member->role_in_project,
                    'rate_per_hour' => (float)$validated['rate_per_hour'],
                    'weekend_multiplier' => $validated['weekend_multiplier'] ?? 1.10,
                    'overtime_multiplier' => $validated['overtime_multiplier'] ?? 1.20,
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        }

        $member->user?->clearPermissionCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data anggota {$member->user->name} berhasil diperbarui.",
                'member' => $member->load('roles', 'user', 'memberRate'),
            ]);
        }

        return back()->with('success', "Data penugasan {$member->user->name} pada proyek ini berhasil diperbarui.");
    }

    /**
     * Remove an assigned user from the project team.
     */
    public function destroy(Request $request, Project $project, ProjectMember $member)
    {
        abort_unless(auth()->user()->can('projects.manage') || auth()->user()->isSuperAdmin(), 403, 'Akses ditolak.');

        if ($member->project_id !== $project->id) {
            abort(404);
        }

        $userName = $member->user->name ?? 'Anggota';
        $user = $member->user;

        $member->delete();

        if ($user) {
            $user->clearPermissionCache();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Anggota {$userName} berhasil dihapus dari proyek.",
            ]);
        }

        return back()->with('success', "Anggota {$userName} berhasil dihapus dari tim proyek.");
    }
}
