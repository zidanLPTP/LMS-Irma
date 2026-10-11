<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SimpanNilaiEsaiRequest;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Services\PenilaianKuisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PenilaianEsaiController extends Controller
{
    public function __construct(
        protected PenilaianKuisService $penilaianService
    ) {}

    /**
     * 1. GET /admin/penilaian-esai: Antrean penilaian esai.
     * Filter menurut kuis dan pencarian nama; urut dari yang terlama diserahkan.
     */
    public function index(Request $request): View
    {
        $query = PercobaanKuis::with(['user', 'kuis', 'jawabanSiswa.soal'])
            ->where('status_pengerjaan', 'selesai')
            ->where('dibatalkan', false)
            ->where('esai_menunggu_nilai', true)
            ->orderBy('waktu_selesai', 'asc');

        if ($request->filled('kuis_id')) {
            $query->where('kuis_id', $request->input('kuis_id'));
        }

        if ($request->filled('cari')) {
            $cari = $request->input('cari');
            $query->whereHas('user', function ($q) use ($cari) {
                $q->where('nama_lengkap', 'like', "%{$cari}%")
                    ->orWhere('username', 'like', "%{$cari}%");
            });
        }

        $daftarPercobaan = $query->paginate(15)->withQueryString();

        $daftarKuis = Kuis::has('soal')->orderBy('judul')->get();

        return view('admin.penilaian-esai.index', compact('daftarPercobaan', 'daftarKuis'));
    }

    /**
     * 2. GET /admin/penilaian-esai/{percobaan}: Form penilaian per percobaan.
     */
    public function show(PercobaanKuis $percobaan): View
    {
        Gate::authorize('nilaiEsai', $percobaan);

        $percobaan->load(['user', 'kuis.soal', 'jawabanSiswa' => function ($q) {
            $q->whereHas('soal', fn ($sq) => $sq->where('tipe_soal', 'esai'));
        }, 'jawabanSiswa.soal']);

        // Cari percobaan berikutnya di antrean untuk tombol "Simpan dan lanjut"
        $percobaanBerikutnya = PercobaanKuis::where('status_pengerjaan', 'selesai')
            ->where('dibatalkan', false)
            ->where('esai_menunggu_nilai', true)
            ->where('id', '!=', $percobaan->id)
            ->orderBy('waktu_selesai', 'asc')
            ->first();

        return view('admin.penilaian-esai.show', compact('percobaan', 'percobaanBerikutnya'));
    }

    /**
     * 3. POST /admin/penilaian-esai/{percobaan}: Simpan nilai esai.
     */
    public function simpan(SimpanNilaiEsaiRequest $request, PercobaanKuis $percobaan): RedirectResponse
    {
        Gate::authorize('nilaiEsai', $percobaan);

        $penilaian = $request->input('penilaian', []);
        $admin = $request->user();

        foreach ($penilaian as $jawabanId => $data) {
            $jawaban = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                ->where('id', $jawabanId)
                ->first();

            if ($jawaban && isset($data['poin'])) {
                $this->penilaianService->nilaiEsai(
                    $jawaban,
                    (float) $data['poin'],
                    $data['catatan'] ?? null,
                    $admin
                );
            }
        }

        $percobaan->refresh();

        // Jika tombol "Simpan dan lanjut ke percobaan berikutnya" ditekan
        if ($request->boolean('lanjut')) {
            $berikutnya = PercobaanKuis::where('status_pengerjaan', 'selesai')
                ->where('dibatalkan', false)
                ->where('esai_menunggu_nilai', true)
                ->where('id', '!=', $percobaan->id)
                ->orderBy('waktu_selesai', 'asc')
                ->first();

            if ($berikutnya) {
                return redirect()->route('admin.penilaian-esai.show', $berikutnya)
                    ->with('sukses', trans('kuis.berhasil_dinilai'));
            }
        }

        // Jika masih ada esai di percobaan ini yang belum dinilai
        if ($percobaan->esai_menunggu_nilai) {
            return redirect()->route('admin.penilaian-esai.show', $percobaan)
                ->with('sukses', trans('kuis.berhasil_dinilai'));
        }

        return redirect()->route('admin.penilaian-esai.index')
            ->with('sukses', trans('kuis.berhasil_dinilai'));
    }
}
