<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasukRequest;
use App\Models\User;
use App\Services\PelacakAksesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasukController extends Controller
{
    /**
     * Tampilkan formulir masuk.
     */
    public function create(): View
    {
        return view('auth.masuk');
    }

    /**
     * Proses autentikasi masuk dengan rate limiting dan proteksi username/email ganda.
     */
    public function store(MasukRequest $request, PelacakAksesService $pelacakAkses): RedirectResponse
    {
        $kunciThrottle = $this->kunciThrottle($request);

        // Batasi percobaan login (maksimal 5 kali percobaan gagal per menit)
        if (RateLimiter::tooManyAttempts($kunciThrottle, 5)) {
            $detik = RateLimiter::availableIn($kunciThrottle);

            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', ['seconds' => $detik]),
            ]);
        }

        $inputLogin = $request->input('login');
        $password = $request->input('password');
        $ingatSaya = $request->boolean('ingat_saya');

        // Tentukan apakah input berupa email atau username
        $jenisLogin = filter_var($inputLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cari user
        $user = User::where($jenisLogin, $inputLogin)->first();

        // Autentikasi
        if (! $user || ! Auth::attempt([$jenisLogin => $inputLogin, 'password' => $password], $ingatSaya)) {
            RateLimiter::hit($kunciThrottle);

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Cek apakah akun aktif
        if (! $user->aktif) {
            Auth::logout();
            RateLimiter::hit($kunciThrottle);

            throw ValidationException::withMessages([
                'login' => trans('auth.nonaktif'),
            ]);
        }

        // Bersihkan hit rate limiter dan regenerasi session
        RateLimiter::clear($kunciThrottle);
        $request->session()->regenerate();

        // Titik pencatatan log sesi masuk siswa
        $pelacakAkses->catatMasuk($user);

        // Arahkan sesuai peran pengguna (admin selalu ke beranda admin agar tidak terpengaruh riwayat sesi siswa)
        if ($user->isAdmin()) {
            return redirect()->route('admin.beranda');
        }

        return redirect()->intended(route('siswa.beranda'));
    }

    /**
     * Proses keluar pengguna.
     */
    public function destroy(Request $request, PelacakAksesService $pelacakAkses): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            // Titik pencatatan log sesi keluar siswa
            $pelacakAkses->catatKeluar($user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }

    /**
     * Buat kunci unik untuk rate limiter berbasis input dan IP.
     */
    protected function kunciThrottle(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('login', '')).'|'.$request->ip());
    }
}
