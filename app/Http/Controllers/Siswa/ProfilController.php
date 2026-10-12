<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\UbahProfilRequest;
use App\Models\RiwayatProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Tampilkan formulir data profil siswa.
     */
    public function edit(Request $request): View
    {
        return view('siswa.profil', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Perbarui data profil siswa dan catat riwayat perubahannya.
     */
    public function update(UbahProfilRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $kolomDitinjau = ['nama_lengkap', 'kelas', 'email'];

        foreach ($kolomDitinjau as $kolom) {
            $nilaiBaru = $validated[$kolom] ?? null;
            $nilaiLama = $user->{$kolom};

            // Simpan perubahan ke riwayat jika ada perbedaan nilai
            if ((string) $nilaiLama !== (string) $nilaiBaru) {
                RiwayatProfil::create([
                    'user_id' => $user->id,
                    'kolom' => $kolom,
                    'nilai_lama' => $nilaiLama,
                    'nilai_baru' => $nilaiBaru,
                ]);

                $user->{$kolom} = $nilaiBaru;
            }
        }

        // Catatan: username tidak disentuh sama sekali demi menjaga keutuhan identitas penelitian
        $user->save();

        return redirect()->route('siswa.profil.edit')
            ->with('status', __('profil.pesan_berhasil_disimpan'));
    }
}
