<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\BukaAksesService;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __construct(
        protected BukaAksesService $bukaAksesService
    ) {}

    /**
     * Tampilkan halaman beranda siswa dengan alur belajar stepper dan ringkasan progres.
     */
    public function index(): View
    {
        $user = auth()->user();

        // 1. Dapatkan daftar langkah belajar dinamis (pretest, bab-bab terbit, posttest)
        $daftarLangkah = $this->bukaAksesService->daftarLangkahBelajar($user);

        // 2. Hitung persentase langkah selesai
        $totalLangkah = count($daftarLangkah);
        $langkahSelesai = count(array_filter($daftarLangkah, fn ($item) => $item['status'] === 'selesai'));
        $persentaseProgres = $totalLangkah > 0 ? (int) round(($langkahSelesai / $totalLangkah) * 100) : 0;

        // 3. Cari langkah terbuka berikutnya untuk tombol "Lanjutkan belajar"
        $langkahLanjutan = null;
        foreach ($daftarLangkah as $langkah) {
            if ($langkah['status'] === 'terbuka') {
                $langkahLanjutan = $langkah;
                break;
            }
        }

        // 4. Ambil maksimal 3 pengumuman aktif yang belum kedaluwarsa (disematkan di atas)
        $pengumumanTerbaru = Pengumuman::aktifUntukSiswa()->limit(3)->get();
        $pengumumanDibacaIds = $user->pengumumanDibaca()->pluck('pengumuman_id')->toArray();

        return view('siswa.beranda', compact(
            'daftarLangkah',
            'totalLangkah',
            'langkahSelesai',
            'persentaseProgres',
            'langkahLanjutan',
            'pengumumanTerbaru',
            'pengumumanDibacaIds'
        ));
    }
}
