<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only).
     */
    public function index()
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Akses ditolak. Hanya Super Admin yang dapat mengelola pengguna.');
        }

        $users = User::latest()->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (Admin only).
     */
    public function create()
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403);
        }
        return view('users.create');
    }

    /**
     * Store a newly created user (Admin only).
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['superadmin', 'staff'])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
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
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil Anda telah berhasil diperbarui.');
    }

    /**
     * Delete a user (Admin only).
     */
    public function destroy(User $user)
    {
        if (auth()->user()->role !== 'superadmin' || $user->id === auth()->id()) {
            abort(403, 'Anda tidak dapat menghapus akun Anda sendiri atau Anda bukan Super Admin.');
        }

        $user->delete();
        return back()->with('success', 'User telah dihapus.');
    }
}
