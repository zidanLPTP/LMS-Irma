<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SimpanPengaturanRequest;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Models\RiwayatPengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /**
     * Tampilkan formulir pengaturan sistem dan riwayat perubahannya.
     */
    public function edit(): View
    {
        $pengaturan = [
            'nama_situs' => Pengaturan::ambil('nama_situs', 'LMS Penelitian Biologi'),
            'logo_situs' => Pengaturan::ambil('logo_situs', null),
            'nilai_lulus' => Pengaturan::ambil('nilai_lulus', '65.00'),
            'batas_waktu_per_soal' => Pengaturan::ambil('batas_waktu_per_soal', '60'),
            'maksimal_kesempatan' => Pengaturan::ambil('maksimal_kesempatan', '2'),
            'pendaftaran_siswa' => Pengaturan::ambil('pendaftaran_siswa', 'buka'),
            'kode_pendaftaran' => Pengaturan::ambil('kode_pendaftaran', null),
        ];

        $adaPercobaanSiswa = PercobaanKuis::exists();

        $riwayatPengaturan = RiwayatPengaturan::with('admin')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return view('admin.pengaturan', compact('pengaturan', 'adaPercobaanSiswa', 'riwayatPengaturan'));
    }

    /**
     * Simpan pembaruan pengaturan sistem.
     */
    public function update(SimpanPengaturanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $admin = $request->user();

        // 1. Simpan Logo jika ada unggahan
        if ($request->hasFile('logo')) {
            $berkas = $request->file('logo');
            $ekstensi = $berkas->getClientOriginalExtension();
            $namaBerkas = 'logo_'.time().'_'.Str::random(8).'.'.$ekstensi;

            // Simpan ke public/unggahan/
            $folderUnggahan = public_path('unggahan');
            if (! is_dir($folderUnggahan)) {
                mkdir($folderUnggahan, 0755, true);
            }
            $berkas->move($folderUnggahan, $namaBerkas);

            $logoPath = 'unggahan/'.$namaBerkas;
            $this->simpanDanCatat('logo_situs', $logoPath, $admin->id);
        }

        // 2. Simpan parameter lainnya
        $daftarKunci = [
            'nama_situs' => $validated['nama_situs'],
            'nilai_lulus' => number_format((float) $validated['nilai_lulus'], 2, '.', ''),
            'batas_waktu_per_soal' => (string) $validated['batas_waktu_per_soal'],
            'maksimal_kesempatan' => (string) $validated['maksimal_kesempatan'],
            'pendaftaran_siswa' => $validated['pendaftaran_siswa'] ?? 'buka',
            'kode_pendaftaran' => $validated['kode_pendaftaran'] ?? null,
        ];

        foreach ($daftarKunci as $kunci => $nilaiBaru) {
            $this->simpanDanCatat($kunci, $nilaiBaru, $admin->id);
        }

        return redirect()->route('admin.pengaturan.edit')
            ->with('sukses', __('pengaturan.pesan.berhasil_disimpan'));
    }

    /**
     * Simpan pengaturan dan catat ke riwayat jika terjadi perubahan nilai.
     */
    protected function simpanDanCatat(string $kunci, ?string $nilaiBaru, int $adminId): void
    {
        $baris = Pengaturan::where('kunci', $kunci)->first();
        $nilaiLama = $baris?->nilai;
        $nilaiDisimpan = $nilaiBaru ?? '';

        if ($baris) {
            if ($nilaiLama !== $nilaiDisimpan) {
                $baris->update(['nilai' => $nilaiDisimpan]);
                RiwayatPengaturan::create([
                    'kunci' => $kunci,
                    'nilai_lama' => $nilaiLama,
                    'nilai_baru' => $nilaiDisimpan,
                    'admin_id' => $adminId,
                ]);
            }
        } else {
            Pengaturan::create([
                'kunci' => $kunci,
                'nilai' => $nilaiDisimpan,
            ]);
            RiwayatPengaturan::create([
                'kunci' => $kunci,
                'nilai_lama' => null,
                'nilai_baru' => $nilaiDisimpan,
                'admin_id' => $adminId,
            ]);
        }
    }
}
