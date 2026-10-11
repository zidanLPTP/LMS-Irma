<?php

namespace App\Http\Middleware;

use App\Services\PelacakAksesService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CatatAktivitas
{
    /**
     * Middleware untuk merekam aktivitas halaman GET siswa secara transparan.
     */
    public function __construct(
        protected PelacakAksesService $pelacakAkses
    ) {}

    /**
     * Jalankan middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya catat untuk permintaan GET berhasil dari siswa
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $user = $request->user();
            if ($user && $user->isSiswa()) {
                $this->pelacakAkses->rekamDetakJantung($user);
            }
        }

        return $response;
    }
}
