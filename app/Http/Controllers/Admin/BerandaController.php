<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bab;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\PercobaanKuis;
use App\Models\User;
use App\Services\BukaAksesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Tampilkan halaman dasbor analitik utama admin.
     */
    public function index(BukaAksesService $bukaAksesService): View
    {
        $zonaWIB = 'Asia/Jakarta';
        $awalHariIniWIB = Carbon::now($zonaWIB)->startOfDay()->utc();
        $akhirHariIniWIB = Carbon::now($zonaWIB)->endOfDay()->utc();

        // 1. Ringkasan Kartu Atas
        $jumlahSiswaAktif = User::where('peran', 'siswa')->where('aktif', true)->count();
        $jumlahSiswaNonaktif = User::where('peran', 'siswa')->where('aktif', false)->count();

        // Siswa unik yang masuk hari ini (hanya siswa, bukan admin)
        $siswaMasukHariIni = LogAkses::whereHas('user', fn ($q) => $q->where('peran', 'siswa'))
            ->whereBetween('waktu_masuk', [$awalHariIniWIB, $akhirHariIniWIB])
            ->distinct('user_id')
            ->count('user_id');

        // Jumlah sesi akses hari ini (hanya siswa)
        $jumlahSesiHariIni = LogAkses::whereHas('user', fn ($q) => $q->where('peran', 'siswa'))
            ->whereBetween('waktu_masuk', [$awalHariIniWIB, $akhirHariIniWIB])
            ->count();

        // Esai menunggu penilaian
        $esaiMenungguNilai = PercobaanKuis::where('status_pengerjaan', 'selesai')
            ->where('dibatalkan', false)
            ->where('esai_menunggu_nilai', true)
            ->count();

        // 2. Tabel "Rata-rata Nilai per Kuis"
        // Ambil urutan kuis resmi: Pretest, Bab Terbit, Posttest
        $daftarKuis = collect();
        $pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        if ($pretest) {
            $daftarKuis->push($pretest);
        }
        $babTerbit = Bab::where('status_terbit', true)->orderBy('urutan')->with('kuis')->get();
        foreach ($babTerbit as $bab) {
            if ($bab->kuis) {
                $daftarKuis->push($bab->kuis);
            }
        }
        $posttest = Kuis::where('tipe_kuis', 'posttest')->first();
        if ($posttest) {
            $daftarKuis->push($posttest);
        }

        $statistikKuis = $daftarKuis->map(function (Kuis $kuis) {
            // Ambil percobaan selesai & tidak dibatalkan yang memiliki nilai akhir sah (tidak null)
            // Nilai tertinggi per siswa
            $nilaiTertinggiPerSiswa = PercobaanKuis::select('user_id', DB::raw('MAX(nilai_akhir) as max_nilai'))
                ->where('kuis_id', $kuis->id)
                ->where('status_pengerjaan', 'selesai')
                ->where('dibatalkan', false)
                ->whereNotNull('nilai_akhir')
                ->whereHas('user', fn ($q) => $q->where('peran', 'siswa'))
                ->groupBy('user_id')
                ->get();

            $jumlahSiswaSelesai = $nilaiTertinggiPerSiswa->count();
            $rataRataNilai = $jumlahSiswaSelesai > 0
                ? round($nilaiTertinggiPerSiswa->avg('max_nilai'), 1)
                : null;

            return [
                'kuis' => $kuis,
                'judul' => $kuis->judul,
                'tipe' => $kuis->tipe_kuis,
                'jumlah_siswa' => $jumlahSiswaSelesai,
                'rata_rata' => $rataRataNilai,
            ];
        });

        // 3. Sebaran Progres Belajar Siswa (Batang CSS)
        // Kategori langkah: Belum Mulai, Pretest, Bab 1..N, Posttest, Tuntas Selesai
        $semuaSiswaAktif = User::where('peran', 'siswa')->get();
        $totalSiswa = $semuaSiswaAktif->count();

        $sebaranProgres = [];
        $kategoriLangkah = ['Belum Mulai'];
        if ($pretest) {
            $kategoriLangkah[] = 'Pretest';
        }
        foreach ($babTerbit as $idx => $bab) {
            $kategoriLangkah[] = 'Bab '.($idx + 1);
        }
        if ($posttest) {
            $kategoriLangkah[] = 'Posttest';
        }
        $kategoriLangkah[] = 'Selesai';

        foreach ($kategoriLangkah as $kat) {
            $sebaranProgres[$kat] = 0;
        }

        foreach ($semuaSiswaAktif as $siswa) {
            $langkahSiswa = $bukaAksesService->daftarLangkahBelajar($siswa);
            $semuaSelesai = true;
            $langkahAktifNama = 'Belum Mulai';

            // Cek apakah belum ada langkah selesai sama sekali
            $adaSelesai = collect($langkahSiswa)->contains(fn ($l) => $l['status'] === 'selesai');

            if (! $adaSelesai) {
                $langkahAktifNama = 'Belum Mulai';
            } else {
                $semuaSelesai = collect($langkahSiswa)->every(fn ($l) => $l['status'] === 'selesai');
                if ($semuaSelesai) {
                    $langkahAktifNama = 'Selesai';
                } else {
                    // Cari langkah pertama yang sedang 'terbuka'
                    $terbuka = collect($langkahSiswa)->firstWhere('status', 'terbuka');
                    if ($terbuka) {
                        if ($terbuka['tipe'] === 'pretest') {
                            $langkahAktifNama = 'Pretest';
                        } elseif ($terbuka['tipe'] === 'posttest') {
                            $langkahAktifNama = 'Posttest';
                        } elseif ($terbuka['tipe'] === 'bab') {
                            // Cari nomor bab terbit
                            $idxBab = $babTerbit->search(fn ($b) => (int) $b->id === (int) $terbuka['model']->id);
                            $langkahAktifNama = $idxBab !== false ? 'Bab '.($idxBab + 1) : 'Bab';
                        }
                    }
                }
            }

            if (isset($sebaranProgres[$langkahAktifNama])) {
                $sebaranProgres[$langkahAktifNama]++;
            } else {
                $sebaranProgres['Belum Mulai']++;
            }
        }

        // 4. Aktivitas Terbaru: 10 penyerahan kuis terakhir
        $aktivitasTerbaru = PercobaanKuis::with(['user', 'kuis'])
            ->where('status_pengerjaan', 'selesai')
            ->where('dibatalkan', false)
            ->whereHas('user', fn ($q) => $q->where('peran', 'siswa'))
            ->latest('waktu_selesai')
            ->limit(10)
            ->get();

        return view('admin.beranda', compact(
            'jumlahSiswaAktif',
            'jumlahSiswaNonaktif',
            'siswaMasukHariIni',
            'jumlahSesiHariIni',
            'esaiMenungguNilai',
            'statistikKuis',
            'sebaranProgres',
            'totalSiswa',
            'aktivitasTerbaru'
        ));
    }
}
