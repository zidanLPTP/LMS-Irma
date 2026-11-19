<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Services\PenilaianKuisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoreksiIsianController extends Controller
{
    public function __construct(
        protected PenilaianKuisService $penilaianService
    ) {}

    /**
     * Tampilkan daftar jawaban isian singkat siswa untuk dikoreksi/diperiksa oleh admin.
     */
    public function index(Request $request): View
    {
        $query = JawabanSiswa::with(['soal.pilihanJawaban', 'percobaanKuis.user', 'percobaanKuis.kuis'])
            ->whereHas('soal', function ($q) {
                $q->where('tipe_soal', 'isian');
            })
            ->whereHas('percobaanKuis', function ($q) {
                $q->where('status_pengerjaan', 'selesai')
                    ->where('dibatalkan', false);
            });

        // Filter berdasarkan kuis
        if ($request->filled('kuis_id')) {
            $query->whereHas('percobaanKuis', function ($q) use ($request) {
                $q->where('kuis_id', $request->input('kuis_id'));
            });
        }

        // Filter pencarian nama siswa atau username
        if ($request->filled('cari')) {
            $cari = $request->input('cari');
            $query->whereHas('percobaanKuis.user', function ($q) use ($cari) {
                $q->where('nama_lengkap', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%");
            });
        }

        // Filter status poin (semua, nol, atau bernilai)
        if ($request->input('filter_poin') === 'nol') {
            $query->where('poin_diperoleh', '<=', 0);
        } elseif ($request->input('filter_poin') === 'bernilai') {
            $query->where('poin_diperoleh', '>', 0);
        }

        $daftarJawaban = $query->latest('id')->paginate(15)->withQueryString();

        // Kuis yang memiliki soal isian singkat
        $daftarKuis = Kuis::whereHas('soal', function ($q) {
            $q->where('tipe_soal', 'isian');
        })->orderBy('judul')->get();

        return view('admin.koreksi-isian.index', compact('daftarJawaban', 'daftarKuis'));
    }

    /**
     * Simpan koreksi nilai isian singkat.
     */
    public function simpan(Request $request, JawabanSiswa $jawaban): RedirectResponse
    {
        $soal = $jawaban->soal;
        abort_unless($soal && $soal->tipe_soal === 'isian', 400, 'Soal bukan bertipe isian singkat.');

        $bobotMaks = (float) $soal->bobot_poin;

        $request->validate([
            'poin' => ['required', 'numeric', 'min:0', "max:{$bobotMaks}"],
        ], [
            'poin.required' => 'Poin nilai wajib diisi.',
            'poin.numeric' => 'Poin nilai harus berupa angka.',
            'poin.min' => 'Poin nilai tidak boleh kurang dari 0.',
            'poin.max' => "Poin nilai tidak boleh melebihi bobot maksimal ({$bobotMaks}).",
        ]);

        $poinBaru = (float) $request->input('poin');

        $this->penilaianService->koreksiNilaiIsian($jawaban, $poinBaru, $request->user());

        $namaSiswa = $jawaban->percobaanKuis?->user?->nama_lengkap ?? 'Siswa';

        return back()->with('sukses', "Nilai isian untuk {$namaSiswa} berhasil diperbarui menjadi {$poinBaru} poin (dari total {$bobotMaks} bobot). Nilai kuis siswa telah dihitung ulang otomatis.");
    }
}
