<?php

namespace App\Services;

use App\Models\LogAkses;
use App\Models\User;

class PelacakAksesService
{
    /**
     * Catat awal sesi login siswa.
     * Buat baris log_akses baru (waktu_masuk = aktivitas_terakhir = sekarang, durasi 0)
     * dan simpan id-nya di sesi.
     */
    public function catatMasuk(User $user): ?LogAkses
    {
        if (! $user->isSiswa()) {
            return null;
        }

        $sekarang = now();

        $log = LogAkses::create([
            'user_id' => $user->id,
            'waktu_masuk' => $sekarang,
            'aktivitas_terakhir' => $sekarang,
            'durasi_detik' => 0,
        ]);

        if (session()) {
            session(['id_log_akses_aktif' => $log->id]);
        }

        return $log;
    }

    /**
     * Rekam detak jantung (heartbeat) aktivitas browser siswa.
     * Bila jeda sejak aktivitas_terakhir <= batas jeda (120 detik), tambahkan jeda ke durasi.
     * Bila jeda > batas, JANGAN tambahkan jeda, tutup log lama, dan buat baris log baru dengan durasi 0.
     */
    public function rekamDetakJantung(User $user): ?LogAkses
    {
        if (! $user->isSiswa()) {
            return null;
        }

        $batasJeda = (int) config('lms.batas_jeda_sesi_detik', 120);
        $sekarang = now();

        // Cari log akses aktif siswa
        $idLogAktif = session('id_log_akses_aktif');
        $log = $idLogAktif ? LogAkses::find($idLogAktif) : null;

        if (! $log || (int) $log->user_id !== (int) $user->id) {
            $log = LogAkses::where('user_id', $user->id)->latest('id')->first();
        }

        // Jika belum ada log sama sekali, buat log baru
        if (! $log) {
            return $this->catatMasuk($user);
        }

        $selisihDetik = abs((int) $log->aktivitas_terakhir->diffInSeconds($sekarang, false));
        $jedaMinimal = (int) config('lms.jeda_minimal_tulis_detik', 5);

        // Jika jeda kurang dari 5 detik, abaikan pembaruan (cegah tulis berlebihan; selisih tetap terhitung nanti)
        if ($selisihDetik < $jedaMinimal) {
            return $log;
        }

        // Jika jeda masih dalam batas toleransi (<= 120 detik)
        if ($selisihDetik <= $batasJeda) {
            $log->update([
                'aktivitas_terakhir' => $sekarang,
                'durasi_detik' => $log->durasi_detik + $selisihDetik,
            ]);

            return $log;
        }

        // Jika jeda melewati batas (mis. laptop sleep / ditinggal lama)
        // Log lama ditutup (aktivitas terakhir tidak diubah, jeda tidak dihitung)
        // Buat sesi log baru
        $logBaru = LogAkses::create([
            'user_id' => $user->id,
            'waktu_masuk' => $sekarang,
            'aktivitas_terakhir' => $sekarang,
            'durasi_detik' => 0,
        ]);

        if (session()) {
            session(['id_log_akses_aktif' => $logBaru->id]);
        }

        return $logBaru;
    }

    /**
     * Catat akhir sesi logout siswa.
     * Tambahkan jeda terakhir bila masih dalam batas toleransi.
     */
    public function catatKeluar(User $user): void
    {
        if (! $user->isSiswa()) {
            return;
        }

        $batasJeda = (int) config('lms.batas_jeda_sesi_detik', 120);
        $sekarang = now();

        $idLogAktif = session('id_log_akses_aktif');
        $log = $idLogAktif ? LogAkses::find($idLogAktif) : null;

        if (! $log || (int) $log->user_id !== (int) $user->id) {
            $log = LogAkses::where('user_id', $user->id)->latest('id')->first();
        }

        if ($log) {
            $selisihDetik = abs((int) $log->aktivitas_terakhir->diffInSeconds($sekarang, false));
            $durasiTambahan = ($selisihDetik <= $batasJeda) ? $selisihDetik : 0;

            $log->update([
                'aktivitas_terakhir' => $sekarang,
                'durasi_detik' => $log->durasi_detik + $durasiTambahan,
            ]);
        }

        if (session()) {
            session()->forget('id_log_akses_aktif');
        }
    }
}
