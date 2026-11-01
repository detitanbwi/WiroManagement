<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login endpoint for WirodevMan mobile companion app.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Only internal staff/admin can use this companion app (clients use Wiromitra portal)
        if ($user->client_id !== null || (isset($user->role) && $user->role === UserRole::CLIENT->value)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Aplikasi ini khusus staf internal Wirodev.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sedang nonaktif. Silakan hubungi Administrator.',
            ], 403);
        }

        $user->update(['last_login_at' => Carbon::now()]);

        // Revoke existing mobile tokens and issue a fresh one
        $user->tokens()->where('name', 'wirodev_mobile')->delete();
        $token = $user->createToken('wirodev_mobile')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'roles' => method_exists($user, 'getRoleSlugs') ? $user->getRoleSlugs() : [$user->role],
                ],
            ],
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'roles' => method_exists($user, 'getRoleSlugs') ? $user->getRoleSlugs() : [$user->role],
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }
}

