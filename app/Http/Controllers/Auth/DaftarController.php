<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DaftarRequest;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\PelacakAksesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DaftarController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran siswa.
     */
    public function create(): View
    {
        $pendaftaranBuka = Pengaturan::ambil('pendaftaran_siswa', 'buka') === 'buka';
        $butuhKode = ! empty(Pengaturan::ambil('kode_pendaftaran'));

        return view('auth.daftar', compact('pendaftaranBuka', 'butuhKode'));
    }

    /**
     * Simpan pendaftaran akun siswa baru.
     */
    public function store(DaftarRequest $request, PelacakAksesService $pelacakAkses): RedirectResponse
    {
        // 1. Cek apakah pendaftaran sedang ditutup oleh administrator
        $pendaftaranBuka = Pengaturan::ambil('pendaftaran_siswa', 'buka') === 'buka';
        if (! $pendaftaranBuka) {
            return back()->with('galat', 'Pendaftaran siswa baru saat ini sedang ditutup oleh administrator.');
        }

        // 2. Cek kode pendaftaran jika diwajibkan
        $kodeWajib = Pengaturan::ambil('kode_pendaftaran');
        if (! empty($kodeWajib)) {
            $kodeInput = (string) $request->input('kode_pendaftaran');
            if (! hash_equals((string) $kodeWajib, $kodeInput)) {
                return back()->withInput()->withErrors([
                    'kode_pendaftaran' => 'Kode pendaftaran tidak sesuai. Silakan hubungi guru pembimbing Anda.',
                ]);
            }
        }

        // Hanya ambil data yang sah dari form request; peran dan aktif tidak diambil
        $dataValid = $request->validated();

        $siswa = User::create([
            'nama_lengkap' => $dataValid['nama_lengkap'],
            'username' => $dataValid['username'],
            'email' => $dataValid['email'],
            'password' => Hash::make($dataValid['password']),
            'kelas' => $dataValid['kelas'] ?? null,
            'peran' => 'siswa', // Mutlak selalu siswa
            'aktif' => true,    // Mutlak selalu aktif
        ]);

        Auth::login($siswa);
        $request->session()->regenerate();

        // Titik pencatatan log sesi masuk siswa
        $pelacakAkses->catatMasuk($siswa);

        return redirect()->route('siswa.beranda');
    }
}
