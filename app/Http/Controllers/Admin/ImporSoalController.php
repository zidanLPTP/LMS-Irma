<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TemplatImporSoalExport;
use App\Http\Controllers\Controller;
use App\Models\Kuis;
use App\Services\ImporSoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporSoalController extends Controller
{
    public function __construct(protected ImporSoalService $imporSoalService) {}

    /**
     * Unduh templat Excel untuk pengisian butir soal kuis.
     */
    public function unduhTemplat(): BinaryFileResponse
    {
        abort_if(! auth()->user()?->isAdmin(), 403);

        return Excel::download(new TemplatImporSoalExport, 'templat_impor_soal_kuis.xlsx');
    }

    /**
     * Formulir unggah berkas impor soal untuk kuis tertentu.
     */
    public function create(Kuis $kuis): View
    {
        Gate::authorize('update', $kuis);

        $sudahAdaPercobaan = $kuis->percobaan()->exists();

        return view('admin.impor-soal.create', compact('kuis', 'sudahAdaPercobaan'));
    }

    /**
     * Tahap 1: Pratinjau validasi berkas Excel soal kuis.
     */
    public function pratinjau(Request $request, Kuis $kuis): View|RedirectResponse
    {
        Gate::authorize('update', $kuis);

        $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:1024'],
        ], [
            'berkas.required' => 'Pilih berkas Excel (.xlsx) untuk diunggah.',
            'berkas.mimes' => 'Format berkas harus berupa dokumen Excel asli (.xlsx).',
            'berkas.max' => 'Ukuran berkas maksimal 1 MB.',
        ]);

        $file = $request->file('berkas');

        try {
            $hasil = $this->imporSoalService->pratinjau($file, $kuis);

            session()->put('impor_soal_pratinjau_'.$kuis->id, [
                'baris_valid' => $hasil['baris_valid'],
                'baris_galat' => $hasil['baris_galat'],
                'nama_berkas' => $hasil['nama_berkas'],
            ]);

            $sudahAdaPercobaan = $kuis->percobaan()->exists();

            return view('admin.impor-soal.pratinjau', compact('kuis', 'hasil', 'sudahAdaPercobaan'));
        } catch (\Throwable $e) {
            return back()->with('galat', 'Gagal memproses berkas Excel soal: '.$e->getMessage());
        }
    }

    /**
     * Tahap 2: Konfirmasi penyimpanan soal secara ATOMIK (semua atau tidak sama sekali).
     */
    public function konfirmasi(Request $request, Kuis $kuis): RedirectResponse
    {
        Gate::authorize('update', $kuis);

        $dataSesi = session()->pull('impor_soal_pratinjau_'.$kuis->id);
        if (! $dataSesi || empty($dataSesi['baris_valid'])) {
            return redirect()->route('admin.kuis.soal.index', $kuis)->with('galat', 'Tidak ada data valid yang dapat dikonfirmasi.');
        }

        try {
            $jumlahBerhasil = $this->imporSoalService->konfirmasi(
                $kuis,
                $dataSesi['baris_valid'],
                $dataSesi['baris_galat'] ?? []
            );

            return redirect()->route('admin.kuis.soal.index', $kuis)
                ->with('sukses', "Berhasil mengimpor {$jumlahBerhasil} butir soal baru ke dalam kuis!");
        } catch (\Throwable $e) {
            return redirect()->route('admin.kuis.soal.index', $kuis)
                ->with('galat', 'Impor soal dibatalkan secara atomik karena kesalahan: '.$e->getMessage());
        }
    }
}
