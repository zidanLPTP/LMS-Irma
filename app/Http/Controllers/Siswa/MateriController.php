<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\Materi;
use App\Models\ProgresMateri;
use App\Services\BukaAksesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function __construct(
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * Tampilkan materi (PDF atau YouTube).
     */
    public function show(Bab $bab, Materi $materi): View|RedirectResponse
    {
        $user = auth()->user();

        // 1. Cegah ID materi milik bab lain
        if ((int) $materi->bab_id !== (int) $bab->id) {
            abort(404, 'Materi tidak sesuai dengan bab yang dipilih.');
        }

        // 2. Cek apakah bab terbuka untuk siswa
        if (! $this->bukaAksesService->apakahBabTerbuka($bab, $user)) {
            return redirect()->route('siswa.beranda')->with(
                'galat',
                'Bab ini masih terkunci. Silakan selesaikan langkah pembelajaran sebelumnya terlebih dahulu.'
            );
        }

        // 3. Catat progres pembukaan materi secara idempoten
        ProgresMateri::firstOrCreate(
            [
                'user_id' => $user->id,
                'materi_id' => $materi->id,
            ],
            [
                'sudah_dibuka' => true,
                'waktu_dibuka' => now(),
            ]
        );

        // 4. Navigasi materi sebelumnya dan berikutnya dalam bab
        $semuaMateri = $bab->materi()->orderBy('urutan')->get();
        $indeksSekarang = $semuaMateri->search(fn ($m) => (int) $m->id === (int) $materi->id);

        $materiSebelumnya = ($indeksSekarang > 0) ? $semuaMateri[$indeksSekarang - 1] : null;
        $materiBerikutnya = ($indeksSekarang < $semuaMateri->count() - 1) ? $semuaMateri[$indeksSekarang + 1] : null;

        // 5. URL PDF privat
        $urlPdfPrivat = ($materi->tipe === 'pdf') ? route('materi.pdf.sajikan', $materi) : null;

        return view('siswa.materi.show', compact(
            'bab',
            'materi',
            'materiSebelumnya',
            'materiBerikutnya',
            'urlPdfPrivat'
        ));
    }
}
