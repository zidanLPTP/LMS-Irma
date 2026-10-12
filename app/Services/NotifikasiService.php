<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiService
{
    /**
     * Kirim notifikasi ramah tanpa duplikasi berbasis referensi unik.
     */
    public function kirim(User $penerima, string $jenis, string $referensi, string $judul, string $isi, ?string $tautan = null): ?Notifikasi
    {
        // Hanya buat jika belum ada notifikasi dengan user_id, jenis, dan referensi yang sama
        $sudahAda = Notifikasi::where('user_id', $penerima->id)
            ->where('jenis', $jenis)
            ->where('referensi', $referensi)
            ->first();

        if ($sudahAda) {
            return $sudahAda;
        }

        return Notifikasi::create([
            'user_id' => $penerima->id,
            'jenis' => $jenis,
            'referensi' => $referensi,
            'judul' => $judul,
            'isi' => $isi,
            'tautan' => $tautan,
            'dibaca_pada' => null,
        ]);
    }

    /**
     * Peristiwa A: Semua esai pada percobaan kuis telah dinilai oleh guru/admin.
     */
    public function notifikasiEsaiDinilai(User $siswa, int $percobaanId, string $judulKuis): ?Notifikasi
    {
        return $this->kirim(
            $siswa,
            'esai_dinilai',
            'percobaan_'.$percobaanId,
            'Nilai Esai Tersedia',
            'Jawaban esai Anda untuk '.$judulKuis.' telah selesai dinilai. Silakan periksa hasil akhir evaluasi Anda.',
            route('siswa.nilai.index')
        );
    }

    /**
     * Peristiwa B: Langkah pembelajaran baru terbuka (Bab 1, Bab berikutnya, atau Posttest).
     */
    public function notifikasiLangkahTerbuka(User $siswa, string $tipeLangkah, int $langkahId, string $namaLangkah): ?Notifikasi
    {
        $referensi = $tipeLangkah.'_'.$langkahId;
        $tautan = $tipeLangkah === 'bab'
            ? route('siswa.bab.show', $langkahId)
            : route('siswa.beranda');

        return $this->kirim(
            $siswa,
            'langkah_terbuka',
            $referensi,
            'Tahapan Belajar Terbuka',
            'Selamat! Tahapan pembelajaran '.$namaLangkah.' kini telah terbuka untuk Anda pelajari.',
            $tautan
        );
    }

    /**
     * Peristiwa C: Kesempatan pengerjaan kuis telah direset oleh admin.
     */
    public function notifikasiKesempatanDireset(User $siswa, int $kuisId, string $judulKuis, int $riwayatTindakanId): ?Notifikasi
    {
        return $this->kirim(
            $siswa,
            'kesempatan_direset',
            'reset_tindakan_'.$riwayatTindakanId,
            'Kesempatan Kuis Direset',
            'Administrator telah mereset kesempatan pengerjaan untuk '.$judulKuis.'. Anda dapat mengerjakan kuis ini kembali.',
            route('siswa.kuis.show', $kuisId)
        );
    }
}
