<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\Materi;
use App\Services\BukaAksesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PencarianController extends Controller
{
    /**
     * Cari judul bab dan materi yang berstatus terbit dan terbuka bagi siswa saat ini.
     */
    public function __invoke(Request $request, BukaAksesService $bukaAksesService): View
    {
        $kataKunci = trim((string) $request->input('q', ''));
        $panjangKarakter = mb_strlen($kataKunci);

        if ($panjangKarakter < 2) {
            return view('siswa.cari', [
                'kataKunci' => $kataKunci,
                'terlaluPendek' => true,
                'hasilBab' => collect(),
                'hasilMateri' => collect(),
            ]);
        }

        $user = $request->user();

        // 1. Ambil bab terbit
        $semuaBabTerbit = Bab::where('status_terbit', true)
            ->orderBy('urutan')
            ->get();

        // Filter HANYA bab yang benar-benar terbuka untuk siswa ini
        $babTerbuka = $semuaBabTerbit->filter(fn (Bab $b) => $bukaAksesService->apakahBabTerbuka($b, $user));
        $babTerbukaIds = $babTerbuka->pluck('id')->all();

        // 2. Cari Bab terbuka yang cocok dengan kata kunci
        $hasilBab = $babTerbuka->filter(function (Bab $b) use ($kataKunci) {
            return mb_stripos($b->judul, $kataKunci) !== false
                || ($b->deskripsi && mb_stripos($b->deskripsi, $kataKunci) !== false);
        });

        // 3. Cari Materi yang berada dalam Bab terbuka yang cocok dengan kata kunci
        $hasilMateri = Materi::whereIn('bab_id', $babTerbukaIds)
            ->where('judul', 'like', "%{$kataKunci}%")
            ->with('bab')
            ->orderBy('urutan')
            ->get();

        return view('siswa.cari', [
            'kataKunci' => $kataKunci,
            'terlaluPendek' => false,
            'hasilBab' => $hasilBab,
            'hasilMateri' => $hasilMateri,
        ]);
    }
}
