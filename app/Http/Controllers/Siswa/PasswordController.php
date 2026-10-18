<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\GantiPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Tampilkan formulir ganti kata sandi.
     */
    public function edit(Request $request): View
    {
        return view('siswa.password', [
            'user' => $request->user(),
            'wajib' => (bool) $request->user()?->wajib_ganti_password,
        ]);
    }

    /**
     * Simpan kata sandi baru siswa.
     */
    public function update(GantiPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->validated('password')),
            'wajib_ganti_password' => false,
        ]);

        $tujuan = $user->isAdmin() ? route('admin.beranda') : route('siswa.beranda');

        return redirect($tujuan)->with('sukses', 'Kata sandi Anda berhasil diperbarui.');
    }
}
