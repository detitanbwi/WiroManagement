<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalUser
{
    /**
     * Handle an incoming request.
     *
     * Ensure the user is an internal staff/management user, not a client portal user.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isInternal()) {
            // If the user has only 'client' role, prohibit access to internal web portal
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda adalah akun portal klien. Silakan akses portal Wiromitra di portal klien.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Akses ditolak. Akun Anda adalah akun portal klien Wiromitra. Silakan gunakan aplikasi portal klien.');
        }

        return $next($request);
    }
}
