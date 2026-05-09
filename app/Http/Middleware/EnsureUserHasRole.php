<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,packer')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun tidak aktif atau belum login.',
            ]);
        }

        if (! empty($roles) && ! $user->hasRole(...$roles)) {
            abort(403, 'Akses ditolak. Role Anda tidak mengizinkan halaman ini.');
        }

        return $next($request);
    }
}
