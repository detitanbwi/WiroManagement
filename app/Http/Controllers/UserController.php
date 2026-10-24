<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users (Super Admin only).
     */
    public function index()
    {
        if (!auth()->user()->can('users.view')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat daftar pengguna.');
        }

        $users = User::with(['roles', 'projects'])->latest()->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (Super Admin only).
     */
    public function create()
    {
        if (!auth()->user()->can('users.create')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menambah pengguna.');
        }

        return view('users.create');
    }

    /**
     * Store a newly created user (Super Admin only).
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('users.create')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk menambah pengguna.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'personal_email' => 'nullable|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'personal_email' => $validated['personal_email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna ' . $user->name . ' berhasil didaftarkan.');
    }

    /**
     * Show the form for editing an existing user (Super Admin only).
     */
    public function edit(User $user)
    {
        if (!auth()->user()->can('users.edit')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengedit pengguna.');
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Update an existing user (Super Admin only).
     */
    public function update(Request $request, User $user)
    {
        if (!auth()->user()->can('users.edit')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengedit pengguna.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'personal_email' => 'nullable|string|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
        ]);

        if ($user->id === auth()->id() && !$request->boolean('is_active', true)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->personal_email = $validated['personal_email'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->is_active = $request->boolean('is_active', true);
        $user->save();

        return redirect()->route('users.index')->with('success', 'Data pengguna ' . $user->name . ' berhasil diperbarui.');
    }

    /**
     * Toggle active/inactive status of a user.
     */
    public function toggleStatus(User $user)
    {
        if (!auth()->user()->can('users.edit')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengubah status pengguna.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->name} berhasil {$statusText}.");
    }

    /**
     * Show the profile edit form.
     */
    public function profile()
    {
        $user = auth()->user();
        return view('users.profile', compact('user'));
    }

    /**
     * Update the profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'personal_email' => 'nullable|string|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->personal_email = $validated['personal_email'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil Anda telah berhasil diperbarui.');
    }

    /**
     * Delete a user (Super Admin only).
     */
    public function destroy(User $user)
    {
        if (!auth()->user()->can('users.delete') || $user->id === auth()->id()) {
            abort(403, 'Anda tidak dapat menghapus akun Anda sendiri atau Anda tidak memiliki izin.');
        }

        $user->roles()->detach();
        $user->delete();

        return back()->with('success', 'User telah dihapus.');
    }
}
