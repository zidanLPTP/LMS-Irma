<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Services\BukaAksesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BabController extends Controller
{
    public function __construct(
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * Tampilkan halaman bab dengan daftar materi terurut.
     */
    public function show(Bab $bab): View|RedirectResponse
    {
        $user = auth()->user();

        // 1. Cek apakah bab terbuka untuk siswa
        if (! $this->bukaAksesService->apakahBabTerbuka($bab, $user)) {
            return redirect()->route('siswa.beranda')->with(
                'galat',
                'Bab ini masih terkunci. Silakan selesaikan langkah pembelajaran sebelumnya terlebih dahulu.'
            );
        }

        // 2. Ambil daftar materi berurutan
        $daftarMateri = $bab->materi()->orderBy('urutan')->get();

        // 3. Status buka dan tanda selesai tiap materi bagi siswa
        $progresMateri = $user->progresMateri()
            ->whereIn('materi_id', $daftarMateri->pluck('id'))
            ->get();

        $materiDibukaIds = $progresMateri
            ->where('sudah_dibuka', true)
            ->pluck('materi_id')
            ->flip()
            ->all();

        $materiSelesaiIds = $progresMateri
            ->whereNotNull('selesai_pada')
            ->pluck('materi_id')
            ->flip()
            ->all();

        $totalMateri = $daftarMateri->count();
        $jumlahMateriSelesai = count($materiSelesaiIds);

        // 4. Kuis bab (bila ada)
        $kuis = $bab->kuis;

        return view('siswa.bab.show', compact(
            'bab',
            'daftarMateri',
            'materiDibukaIds',
            'materiSelesaiIds',
            'totalMateri',
            'jumlahMateriSelesai',
            'kuis'
        ));
    }
}
