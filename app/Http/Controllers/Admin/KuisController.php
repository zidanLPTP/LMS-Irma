<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KuisRequest;
use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\Pengaturan;
use App\Models\RiwayatNilaiEsai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KuisController extends Controller
{
    public function index(): View
    {
        $daftarKuis = Kuis::with('bab')->withCount('soal', 'percobaan')->get();

        return view('admin.kuis.index', compact('daftarKuis'));
    }

    public function create(): View
    {
        $daftarBab = Bab::orderBy('urutan')->get();
        $pengaturanDefault = [
            'batas_waktu_per_soal' => Pengaturan::ambil('batas_waktu_per_soal', 60),
            'nilai_lulus' => Pengaturan::ambil('nilai_lulus', '65.00'),
            'maksimal_kesempatan' => Pengaturan::ambil('maksimal_kesempatan', 2),
        ];

        return view('admin.kuis.create', compact('daftarBab', 'pengaturanDefault'));
    }

    public function store(KuisRequest $request): RedirectResponse
    {
        $tipeKuis = $request->validated('tipe_kuis');

        // Untuk pretest dan posttest, maksimal kesempatan selalu 1
        $maksimalKesempatan = in_array($tipeKuis, ['pretest', 'posttest'], true)
            ? 1
            : $request->validated('maksimal_kesempatan');

        $babId = ($tipeKuis === 'kuis_bab') ? $request->validated('bab_id') : null;

        Kuis::create([
            'tipe_kuis' => $tipeKuis,
            'bab_id' => $babId,
            'judul' => $request->validated('judul'),
            'batas_waktu_per_soal' => $request->validated('batas_waktu_per_soal'),
            'nilai_lulus' => $request->validated('nilai_lulus'),
            'maksimal_kesempatan' => $maksimalKesempatan,
            'acak_soal' => $request->boolean('acak_soal', true),
        ]);

        return redirect()->route('admin.kuis.index')->with('sukses', 'Kuis baru berhasil dibuat.');
    }

    public function edit(Kuis $kuis): View
    {
        $daftarBab = Bab::orderBy('urutan')->get();
        $pengaturanDefault = [
            'batas_waktu_per_soal' => Pengaturan::ambil('batas_waktu_per_soal', 60),
            'nilai_lulus' => Pengaturan::ambil('nilai_lulus', '65.00'),
            'maksimal_kesempatan' => Pengaturan::ambil('maksimal_kesempatan', 2),
        ];

        return view('admin.kuis.edit', compact('kuis', 'daftarBab', 'pengaturanDefault'));
    }

    public function update(KuisRequest $request, Kuis $kuis): RedirectResponse
    {
        $tipeKuis = $request->validated('tipe_kuis');

        $maksimalKesempatan = in_array($tipeKuis, ['pretest', 'posttest'], true)
            ? 1
            : $request->validated('maksimal_kesempatan');

        $babId = ($tipeKuis === 'kuis_bab') ? $request->validated('bab_id') : null;

        $kuis->update([
            'tipe_kuis' => $tipeKuis,
            'bab_id' => $babId,
            'judul' => $request->validated('judul'),
            'batas_waktu_per_soal' => $request->validated('batas_waktu_per_soal'),
            'nilai_lulus' => $request->validated('nilai_lulus'),
            'maksimal_kesempatan' => $maksimalKesempatan,
            'acak_soal' => $request->boolean('acak_soal', true),
        ]);

        return redirect()->route('admin.kuis.index')->with('sukses', 'Data kuis berhasil diperbarui.');
    }

    public function destroy(Kuis $kuis): RedirectResponse
    {
        // Integritas data penelitian: Kuis yang sudah memiliki percobaan siswa tidak boleh dihapus
        if ($kuis->percobaan()->exists()) {
            return redirect()->route('admin.kuis.index')->with('galat', 'Kuis ini tidak dapat dihapus karena sudah memiliki riwayat pengerjaan siswa yang merupakan data penelitian.');
        }

        $kuis->delete();

        return redirect()->route('admin.kuis.index')->with('sukses', 'Kuis berhasil dihapus.');
    }

    /**
     * Reset riwayat pengerjaan kuis siswa (hapus semua percobaan & jawaban).
     * Berguna bila admin salah input soal dan ingin membersihkan kuis agar bisa diedit/dihapus kembali.
     */
    public function resetPengerjaan(Request $request, Kuis $kuis): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        DB::transaction(function () use ($kuis) {
            $percobaanIds = $kuis->percobaan()->pluck('id');

            // Hapus riwayat penilaian esai
            RiwayatNilaiEsai::whereIn(
                'jawaban_siswa_id',
                JawabanSiswa::whereIn('percobaan_kuis_id', $percobaanIds)->pluck('id')
            )->delete();

            // Hapus jawaban siswa
            JawabanSiswa::whereIn('percobaan_kuis_id', $percobaanIds)->delete();

            // Hapus percobaan kuis
            $kuis->percobaan()->delete();
        });

        return redirect()->route('admin.kuis.index')
            ->with('sukses', "Riwayat pengerjaan kuis '{$kuis->judul}' berhasil dibersihkan. Kuis dan butir soal sekarang sudah bebas diedit atau dihapus kembali.");
    }
}
