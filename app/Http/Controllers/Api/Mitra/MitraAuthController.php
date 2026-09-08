<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClientCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MitraAuthController extends Controller
{
    protected ClientCredentialService $credentialService;

    public function __construct(ClientCredentialService $credentialService)
    {
        $this->credentialService = $credentialService;
    }

    /**
     * Verifikasi token invitation untuk onboarding Wiromitra.
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $result = $this->credentialService->verifyToken($request->token);

        if (!$result['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Pasang password pertama kali dari link undangan Wiromitra.
     */
    public function setPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $authData = $this->credentialService->acceptInvitation($request->token, $request->password);

            return response()->json([
                'status' => 'success',
                'message' => 'Akun berhasil diaktifkan. Selamat datang di Wiromitra!',
                'data' => $authData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Login klien ke portal Wiromitra.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('client')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial yang Anda masukkan salah.',
            ], 401);
        }

        if (!$user->hasRole('client')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Akun ini bukan akun portal klien Wiromitra.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sedang nonaktif. Silakan hubungi tim PM.',
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => Carbon::now()]);

        // Hapus token lama dan buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('wiromitra_session')->plainTextToken;

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
                    'roles' => $user->getRoleSlugs(),
                    'client_id' => $user->client_id,
                    'must_change_password' => (bool)$user->must_change_password,
                    'company_name' => $user->client ? $user->client->company_name : null,
                    'client_name' => $user->client ? $user->client->name : null,
                ]
            ]
        ]);
    }

    /**
     * Dapatkan data profil user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('client');

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'roles' => $user->getRoleSlugs(),
                    'client_id' => $user->client_id,
                    'company_name' => $user->client ? $user->client->company_name : null,
                    'client_name' => $user->client ? $user->client->name : null,
                    'phone' => $user->client ? $user->client->phone : null,
                    'address' => $user->client ? $user->client->address : null,
                ]
            ]
        ]);
    }

    /**
     * Logout dari Wiromitra.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout.',
        ]);
    }
}
