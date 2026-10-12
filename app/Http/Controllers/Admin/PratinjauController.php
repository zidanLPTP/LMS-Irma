<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\Kuis;
use App\Models\Materi;
use Illuminate\View\View;

class PratinjauController extends Controller
{
    /**
     * Pratinjau Bab seperti tampilan siswa tanpa mencatat progres atau log.
     */
    public function bab(Bab $bab): View
    {
        $daftarMateri = $bab->materi()->orderBy('urutan')->get();
        $kuis = $bab->kuis;
        $materiDibukaIds = [];
        $materiSelesaiIds = [];
        $totalMateri = $daftarMateri->count();
        $jumlahMateriSelesai = 0;
        $modePratinjau = true;

        return view('admin.pratinjau.bab', compact(
            'bab',
            'daftarMateri',
            'kuis',
            'materiDibukaIds',
            'materiSelesaiIds',
            'totalMateri',
            'jumlahMateriSelesai',
            'modePratinjau'
        ));
    }

    /**
     * Pratinjau Materi (PDF / YouTube) seperti tampilan siswa tanpa mencatat progres.
     */
    public function materi(Bab $bab, Materi $materi): View
    {
        if ((int) $materi->bab_id !== (int) $bab->id) {
            abort(404);
        }

        $semuaMateri = $bab->materi()->orderBy('urutan')->get();
        $indeksSekarang = $semuaMateri->search(fn ($m) => (int) $m->id === (int) $materi->id);

        $materiSebelumnya = ($indeksSekarang > 0) ? $semuaMateri[$indeksSekarang - 1] : null;
        $materiBerikutnya = ($indeksSekarang < $semuaMateri->count() - 1) ? $semuaMateri[$indeksSekarang + 1] : null;
        $urlPdfPrivat = ($materi->tipe === 'pdf') ? route('materi.pdf.sajikan', $materi) : null;
        $selesai = false;
        $modePratinjau = true;

        return view('admin.pratinjau.materi', compact(
            'bab',
            'materi',
            'materiSebelumnya',
            'materiBerikutnya',
            'urlPdfPrivat',
            'selesai',
            'modePratinjau'
        ));
    }

    /**
     * Pratinjau Kuis seperti tampilan siswa ditambah bagian terpisah khusus admin yang menandai kunci jawaban.
     */
    public function kuis(Kuis $kuis): View
    {
        $kuis->load(['soal.pilihanJawaban']);
        $modePratinjau = true;

        return view('admin.pratinjau.kuis', compact('kuis', 'modePratinjau'));
    }
}
