<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PeriksaAkunAktifDanPassword
{
    /**
     * Pastikan akun siswa aktif dan tangani kewajiban ganti password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // 1. Akun nonaktif: keluarkan sesi saat itu juga
            if (! $user->aktif) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('masuk')->with('galat', 'Akun Anda dinonaktifkan oleh administrator. Silakan hubungi pihak sekolah.');
            }

            // 2. Wajib ganti password: jika true, paksa ke halaman ganti password
            if ($user->wajib_ganti_password) {
                $ruteDiizinkan = [
                    'siswa.password.edit',
                    'siswa.password.update',
                    'keluar',
                ];

                if (! in_array($request->route()?->getName(), $ruteDiizinkan, true)) {
                    return redirect()->route('siswa.password.edit')
                        ->with('peringatan', 'Kata sandi Anda perlu diganti. Silakan buat kata sandi baru untuk melanjutkan.');
                }
            }
        }

        return $next($request);
    }
}
