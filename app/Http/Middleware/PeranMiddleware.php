<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeranMiddleware
{
    /**
     * Tangani permintaan masuk dan validasi peran pengguna.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('masuk'));
        }

        if ($user->peran !== $peran) {
            abort(403, 'Akses tidak diizinkan untuk peran ini.');
        }

        return $next($request);
    }
}
