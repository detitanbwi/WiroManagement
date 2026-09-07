<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class ClientCredentialService
{
    /**
     * Buat invitation token aman untuk onboarding klien mandiri.
     */
    public function generateInvitation(Client $client): array
    {
        if (empty($client->email)) {
            throw new Exception("Klien harus memiliki alamat email untuk menerima kredensial.");
        }

        // Batalkan invitation lama yang belum diterima
        ClientInvitation::where('client_id', $client->id)
            ->whereNull('accepted_at')
            ->delete();

        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = Carbon::now()->addHours(72);

        $invitation = ClientInvitation::create([
            'client_id' => $client->id,
            'email' => $client->email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $portalUrl = config('app.mitra_url', 'http://localhost:4200') . '/set-password?token=' . $rawToken;

        return [
            'invitation_id' => $invitation->id,
            'raw_token' => $rawToken,
            'activation_url' => $portalUrl,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Buat kredensial instan (username & password acak) langsung dari dashboard PM.
     */
    public function createInstantCredentials(Client $client): array
    {
        if (empty($client->email)) {
            throw new Exception("Klien harus memiliki alamat email untuk dibuatkan kredensial.");
        }

        $rawPassword = Str::password(10, symbols: false) . rand(10, 99);

        // Cari user yang sudah ada dengan email yang sama atau buat baru
        $user = User::where('email', $client->email)->first();

        if ($user) {
            $user->update([
                'name' => $client->name,
                'password' => Hash::make($rawPassword),
                'role' => 'client',
                'client_id' => $client->id,
                'is_active' => true,
                'must_change_password' => true,
            ]);
        } else {
            $user = User::create([
                'name' => $client->name,
                'email' => $client->email,
                'password' => Hash::make($rawPassword),
                'role' => 'client',
                'client_id' => $client->id,
                'is_active' => true,
                'must_change_password' => true,
            ]);
        }

        return [
            'user' => $user,
            'email' => $client->email,
            'temporary_password' => $rawPassword,
            'portal_url' => config('app.mitra_url', 'http://localhost:4200') . '/login',
        ];
    }

    /**
     * Validasi token aktivasi dan pasang password baru oleh klien di Wiromitra.
     */
    public function acceptInvitation(string $rawToken, string $password): array
    {
        $tokenHash = hash('sha256', $rawToken);

        $invitation = ClientInvitation::with('client')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation) {
            throw new Exception("Token aktivasi tidak valid.");
        }

        if ($invitation->isExpired()) {
            throw new Exception("Token aktivasi telah kadaluarsa. Silakan hubungi Project Manager Anda.");
        }

        if ($invitation->isAccepted()) {
            throw new Exception("Token aktivasi sudah pernah digunakan sebelumnya.");
        }

        $client = $invitation->client;

        // Buat atau update user akun klien
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            $user->update([
                'name' => $client->name,
                'password' => Hash::make($password),
                'role' => 'client',
                'client_id' => $client->id,
                'is_active' => true,
                'must_change_password' => false,
                'last_login_at' => Carbon::now(),
            ]);
        } else {
            $user = User::create([
                'name' => $client->name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'role' => 'client',
                'client_id' => $client->id,
                'is_active' => true,
                'must_change_password' => false,
                'last_login_at' => Carbon::now(),
            ]);
        }

        $invitation->update(['accepted_at' => Carbon::now()]);

        // Buat Bearer Token Sanctum
        $token = $user->createToken('wiromitra_session')->plainTextToken;

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'client_id' => $user->client_id,
                'company_name' => $client->company_name,
            ]
        ];
    }

    /**
     * Cek validitas token sebelum menampilkan form set-password di Wiromitra.
     */
    public function verifyToken(string $rawToken): array
    {
        $tokenHash = hash('sha256', $rawToken);

        $invitation = ClientInvitation::with('client')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            return [
                'valid' => false,
                'message' => 'Token aktivasi tidak valid atau telah kadaluarsa.'
            ];
        }

        return [
            'valid' => true,
            'email' => $invitation->email,
            'client_name' => $invitation->client->name,
            'company_name' => $invitation->client->company_name,
        ];
    }
}
