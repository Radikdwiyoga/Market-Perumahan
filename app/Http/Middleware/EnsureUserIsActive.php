<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak request dari akun yang sudah dinonaktifkan pengelola.
 *
 * `users.status` selama ini hanya diperiksa saat login, sehingga sesi yang sudah
 * terbit tetap bisa dipakai penuh setelah akun dinonaktifkan dari panel admin.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->status === 'active') {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            // Token sanctum ikut dicabut supaya token curian tidak bisa dipakai ulang.
            $request->user()?->currentAccessToken()?->delete();
            Auth::guard('web')->logout();

            return response()->json(['message' => 'Akun Anda dinonaktifkan oleh pengelola.'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['login' => 'Akun Anda dinonaktifkan oleh pengelola.']);
    }
}
