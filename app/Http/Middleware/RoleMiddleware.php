<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Super Admin always has full access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // If specific roles are passed, verify user has at least one of them
        if (!empty($roles) && !$user->hasAnyRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
                    'required_roles' => $roles,
                ], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki peran yang diizinkan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
