<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\JawabanSiswa;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Services\BukaAksesService;
use App\Services\PenilaianKuisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiSayaController extends Controller
{
    /**
     * Tampilkan rekapitulasi nilai dan riwayat percobaan siswa per langkah belajar.
     */
    public function index(Request $request, BukaAksesService $bukaAksesService, PenilaianKuisService $penilaianKuisService): View
    {
        $siswa = $request->user();

        // Ambil urutan langkah belajar resmi yang terbit
        $daftarLangkah = $bukaAksesService->daftarLangkahBelajar($siswa);

        $dataLangkah = [];

        foreach ($daftarLangkah as $langkah) {
            $kuis = null;
            if ($langkah['tipe'] === 'pretest' || $langkah['tipe'] === 'posttest') {
                $kuis = $langkah['model'];
            } elseif ($langkah['tipe'] === 'bab') {
                $kuis = $langkah['model']->kuis;
            }

            if (! $kuis) {
                continue;
            }

            // Ambil nilai tertinggi yang sah (hanya dari percobaan aktif yang selesai, bukan dibatalkan)
            $nilaiTertinggi = $penilaianKuisService->ambilNilaiTertinggi($kuis, $siswa);

            // Ambil semua percobaan kuis siswa yang sah (bukan dibatalkan)
            $riwayatPercobaan = PercobaanKuis::where('kuis_id', $kuis->id)
                ->where('user_id', $siswa->id)
                ->where('dibatalkan', false)
                ->orderBy('nomor_percobaan', 'asc')
                ->get()
                ->map(function ($percobaan) {
                    // Ambil catatan guru dari jawaban esai yang telah dinilai pada percobaan ini
                    $catatanGuru = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                        ->whereNotNull('catatan_guru')
                        ->where('catatan_guru', '!=', '')
                        ->pluck('catatan_guru')
                        ->toArray();

                    return [
                        'id' => $percobaan->id,
                        'nomor_percobaan' => $percobaan->nomor_percobaan,
                        'status' => $percobaan->status_pengerjaan,
                        'waktu_mulai' => $percobaan->waktu_mulai,
                        'waktu_selesai' => $percobaan->waktu_selesai,
                        'nilai_otomatis' => $percobaan->nilai_otomatis,
                        'nilai_esai' => $percobaan->nilai_esai,
                        'nilai_akhir' => $percobaan->nilai_akhir,
                        'catatan_guru' => $catatanGuru,
                    ];
                });

            // Tentukan status kelulusan untuk kuis bab
            $statusLulus = null;
            if ($kuis->tipe_kuis === 'kuis_bab' && $nilaiTertinggi !== null) {
                $nilaiLulusMin = $kuis->nilai_lulus ?? (float) Pengaturan::ambil('nilai_lulus', config('lms.nilai_lulus_default', 65));
                $statusLulus = $nilaiTertinggi >= $nilaiLulusMin;
            }

            $dataLangkah[] = [
                'langkah' => $langkah,
                'kuis' => $kuis,
                'nilai_tertinggi' => $nilaiTertinggi,
                'status_lulus' => $statusLulus,
                'riwayat_percobaan' => $riwayatPercobaan,
            ];
        }

        return view('siswa.nilai.index', [
            'daftarLangkah' => $dataLangkah,
        ]);
    }
}
