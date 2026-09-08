<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of all roles and their permissions.
     */
    public function index()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang dapat mengelola peran dan hak akses.');
        }

        $roles = Role::withCount('users', 'permissions')
            ->with(['permissions' => function ($q) {
                $q->orderBy('group');
            }])
            ->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $permissionGroups = Permission::grouped();
        $colors = $this->getColorOptions();

        return view('roles.create', compact('permissionGroups', 'colors'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'slug' => 'nullable|string|max:50|unique:roles,slug',
            'color' => 'required|string|in:purple,indigo,blue,emerald,amber,rose,cyan,slate',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        // Prevent duplicate slug if auto-generated
        if (Role::where('slug', $slug)->exists()) {
            return back()->withInput()->withErrors(['slug' => 'Slug peran sudah digunakan. Silakan gunakan nama atau slug lain.']);
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', "Peran '{$role->name}' berhasil dibuat dengan " . count($validated['permissions'] ?? []) . " hak akses.");
    }

    /**
     * Show the form for editing an existing role (both custom and predefined).
     */
    public function edit(Role $role)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $permissionGroups = Permission::grouped();
        $currentPermissions = $role->permissions->pluck('name')->toArray();
        $colors = $this->getColorOptions();

        return view('roles.edit', compact('role', 'permissionGroups', 'currentPermissions', 'colors'));
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, Role $role)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
            'color' => 'required|string|in:purple,indigo,blue,emerald,amber,rose,cyan,slate',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // If not a system role, allow updating name
        if (!$role->is_system) {
            $role->name = $validated['name'];
        }

        $role->color = $validated['color'];
        $role->description = $validated['description'] ?? null;
        $role->save();

        // Update permissions
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', "Konfigurasi hak akses peran '{$role->name}' berhasil diperbarui.");
    }

    /**
     * Remove a custom role from system.
     */
    public function destroy(Role $role)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($role->is_system) {
            return back()->with('error', 'Peran bawaan sistem tidak dapat dihapus.');
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return back()->with('error', "Peran '{$role->name}' tidak dapat dihapus karena masih digunakan oleh {$userCount} pengguna.");
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Peran '{$role->name}' berhasil dihapus.");
    }

    private function getColorOptions(): array
    {
        return [
            'purple' => ['label' => 'Purple / Ungu Elegan', 'class' => 'bg-purple-100 text-purple-800 border-purple-200'],
            'indigo' => ['label' => 'Indigo / Nila Eksekutif', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
            'blue' => ['label' => 'Blue / Biru Profesional', 'class' => 'bg-blue-100 text-blue-800 border-blue-200'],
            'emerald' => ['label' => 'Emerald / Hijau Keuangan', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
            'amber' => ['label' => 'Amber / Kuning QA & Alert', 'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
            'rose' => ['label' => 'Rose / Merah Muda Spesial', 'class' => 'bg-rose-100 text-rose-800 border-rose-200'],
            'cyan' => ['label' => 'Cyan / Biru Terang', 'class' => 'bg-cyan-100 text-cyan-800 border-cyan-200'],
            'slate' => ['label' => 'Slate / Abu Staf Netral', 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
        ];
    }
}
