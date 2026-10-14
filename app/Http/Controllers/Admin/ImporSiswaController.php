<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TemplatImporSiswaExport;
use App\Exports\UnduhAkunSementaraExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImporSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporSiswaController extends Controller
{
    public function __construct(protected ImporSiswaService $imporSiswaService) {}

    /**
     * Unduh templat Excel untuk pengisian data siswa.
     */
    public function unduhTemplat(): BinaryFileResponse
    {
        Gate::authorize('create', User::class);

        return Excel::download(new TemplatImporSiswaExport, 'templat_impor_siswa.xlsx');
    }

    /**
     * Formulir unggah berkas impor siswa.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.impor-siswa.create');
    }

    /**
     * Tahap 1: Pratinjau validasi berkas Excel siswa.
     */
    public function pratinjau(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', User::class);

        $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:1024'], // max 1 MB
        ], [
            'berkas.required' => 'Pilih berkas Excel (.xlsx) untuk diunggah.',
            'berkas.mimes' => 'Format berkas harus berupa dokumen Excel asli (.xlsx).',
            'berkas.max' => 'Ukuran berkas maksimal 1 MB.',
        ]);

        $file = $request->file('berkas');

        try {
            $hasil = $this->imporSiswaService->pratinjau($file);

            // Simpan data baris valid sementara ke session untuk dikonfirmasi
            session()->put('impor_siswa_pratinjau', [
                'baris_valid' => $hasil['baris_valid'],
                'nama_berkas' => $hasil['nama_berkas'],
                'jumlah_gagal' => count($hasil['baris_galat']),
            ]);

            return view('admin.impor-siswa.pratinjau', compact('hasil'));
        } catch (\Throwable $e) {
            return back()->with('galat', 'Gagal memproses berkas Excel: '.$e->getMessage());
        }
    }

    /**
     * Tahap 2: Konfirmasi penyimpanan baris valid ke database.
     */
    public function konfirmasi(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', User::class);

        $dataSesi = session()->pull('impor_siswa_pratinjau');
        if (! $dataSesi || empty($dataSesi['baris_valid'])) {
            return redirect()->route('admin.impor-siswa.create')->with('galat', 'Tidak ada data valid yang dapat dikonfirmasi.');
        }

        $hasilAkun = $this->imporSiswaService->konfirmasi(
            $dataSesi['baris_valid'],
            $request->user()->id,
            $dataSesi['nama_berkas'],
            $dataSesi['jumlah_gagal'] ?? 0
        );

        // Simpan hasil akun baru ke flash session untuk SATU KALI tampilan dan unduhan
        session()->flash('hasil_impor_siswa', $hasilAkun);

        return view('admin.impor-siswa.selesai', compact('hasilAkun'));
    }

    /**
     * Unduh daftar akun dan kata sandi sementara dari sesi (TIDAK dari disk/database).
     */
    public function unduhAkun(Request $request): BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('create', User::class);

        $daftarAkun = session('hasil_impor_siswa');
        if (! $daftarAkun) {
            return redirect()->route('admin.pengguna.index')->with('galat', 'Sesi data kata sandi sementara telah berakhir dan tidak dapat diakses lagi.');
        }

        // Hapus dari sesi setelah diunduh sehingga tidak bisa diunduh ulang (sekali unduh/tampil)
        session()->forget('hasil_impor_siswa');

        return Excel::download(new UnduhAkunSementaraExport($daftarAkun), 'daftar_akun_siswa_sementara.xlsx');
    }
}
