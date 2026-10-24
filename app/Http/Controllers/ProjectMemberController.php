<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    /**
     * Assign a user to the project team with selected roles.
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
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,slug',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $member = $project->assignMember($user, $validated['roles']);

        $user->clearPermissionCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Pengguna {$user->name} berhasil ditugaskan ke proyek.",
                'member' => $member->load('roles', 'user'),
            ]);
        }

        return back()->with('success', "Anggota tim {$user->name} berhasil ditugaskan ke proyek dengan " . count($validated['roles']) . ' peran.');
    }

    /**
     * Update roles for an assigned project member.
     */
    public function update(Request $request, Project $project, ProjectMember $member)
    {
        abort_unless(auth()->user()->can('projects.manage') || auth()->user()->isSuperAdmin(), 403, 'Akses ditolak.');

        // Ensure member belongs to this project
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,slug',
        ]);

        $member->syncRoles($validated['roles']);
        $member->user->clearPermissionCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Peran anggota {$member->user->name} berhasil diperbarui.",
                'member' => $member->load('roles', 'user'),
            ]);
        }

        return back()->with('success', "Peran anggota {$member->user->name} pada proyek ini berhasil diperbarui.");
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
