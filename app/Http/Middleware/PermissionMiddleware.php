<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Super Admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!empty($permissions) && !$user->hasAnyPermission($permissions)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
                    'required_permissions' => $permissions,
                ], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin yang diperlukan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
