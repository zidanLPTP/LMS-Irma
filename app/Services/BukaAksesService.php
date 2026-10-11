<?php

namespace App\Services;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\ProgresMateri;
use App\Models\User;

class BukaAksesService
{
    /**
     * Cek apakah pretest, kuis bab, atau posttest terbuka untuk siswa.
     */
    public function apakahKuisTerbuka(Kuis $kuis, User $user): bool
    {
        // 1. Pretest: selalu terbuka untuk siswa aktif, tertutup setelah selesai (1x)
        if ($kuis->tipe_kuis === 'pretest') {
            $sudahSelesai = PercobaanKuis::where('kuis_id', $kuis->id)
                ->where('user_id', $user->id)
                ->where('dibatalkan', false)
                ->where('status_pengerjaan', 'selesai')
                ->exists();

            return ! $sudahSelesai;
        }

        // 2. Kuis Bab: terbuka selama bab-nya terbuka
        if ($kuis->tipe_kuis === 'kuis_bab') {
            if (! $kuis->bab) {
                return false;
            }

            return $this->apakahBabTerbuka($kuis->bab, $user);
        }

        // 3. Posttest: terbuka bila bab terakhir yang terbit sudah selesai, tertutup jika sudah selesai (1x)
        if ($kuis->tipe_kuis === 'posttest') {
            $sudahSelesai = PercobaanKuis::where('kuis_id', $kuis->id)
                ->where('user_id', $user->id)
                ->where('dibatalkan', false)
                ->where('status_pengerjaan', 'selesai')
                ->exists();

            if ($sudahSelesai) {
                return false;
            }

            $babTerbit = Bab::where('status_terbit', true)->orderBy('urutan')->get();
            if ($babTerbit->isEmpty()) {
                return $this->apakahPretestSelesai($user);
            }

            $babTerakhir = $babTerbit->last();

            return $this->apakahBabSelesai($babTerakhir, $user);
        }

        return false;
    }

    /**
     * Cek apakah sebuah Bab terbuka untuk siswa.
     * Hanya bab dengan status_terbit = true yang dapat dibuka.
     */
    public function apakahBabTerbuka(Bab $bab, User $user): bool
    {
        if (! $bab->status_terbit) {
            return false;
        }

        // Syarat mutlak: Pretest harus sudah selesai
        if (! $this->apakahPretestSelesai($user)) {
            return false;
        }

        // Dapatkan semua bab terbit diurutkan berdasarkan urutan
        $semuaBabTerbit = Bab::where('status_terbit', true)->orderBy('urutan')->get();
        $indexBab = $semuaBabTerbit->search(fn ($b) => (int) $b->id === (int) $bab->id);

        if ($indexBab === false) {
            return false;
        }

        // Bab pertama yang terbit otomatis terbuka jika pretest selesai
        if ($indexBab === 0) {
            return true;
        }

        // Bab N terbuka jika bab sebelumnya (urutan terbit) sudah berstatus selesai
        $babSebelumnya = $semuaBabTerbit[$indexBab - 1];

        return $this->apakahBabSelesai($babSebelumnya, $user);
    }

    /**
     * Cek apakah sebuah bab berstatus selesai bagi siswa.
     */
    public function apakahBabSelesai(Bab $bab, User $user): bool
    {
        $kuis = $bab->kuis;

        // Cabang 1 & 2: Bab memiliki kuis
        if ($kuis) {
            $soalOtomatisAda = $kuis->soal()
                ->whereIn('tipe_soal', ['pilihan_ganda', 'isian'])
                ->exists();

            // Punya soal otomatis: lulus ATAU belum_lulus_lanjut
            if ($soalOtomatisAda) {
                return PercobaanKuis::where('kuis_id', $kuis->id)
                    ->where('user_id', $user->id)
                    ->where('dibatalkan', false)
                    ->where('status_pengerjaan', 'selesai')
                    ->whereIn('status_kelulusan', ['lulus', 'belum_lulus_lanjut'])
                    ->exists();
            }

            // Kuis hanya berisi esai: cukup ada percobaan yang selesai (diserahkan)
            $soalEsaiAda = $kuis->soal()->where('tipe_soal', 'esai')->exists();
            if ($soalEsaiAda) {
                return PercobaanKuis::where('kuis_id', $kuis->id)
                    ->where('user_id', $user->id)
                    ->where('dibatalkan', false)
                    ->where('status_pengerjaan', 'selesai')
                    ->exists();
            }
        }

        // Cabang 3: Bab tanpa kuis (atau kuis tanpa butir soal) -> semua materi bab sudah dibuka
        $daftarMateriIds = $bab->materi()->pluck('id');
        if ($daftarMateriIds->isEmpty()) {
            return true;
        }

        $jumlahMateriDibuka = ProgresMateri::where('user_id', $user->id)
            ->whereIn('materi_id', $daftarMateriIds)
            ->where('sudah_dibuka', true)
            ->count();

        return $jumlahMateriDibuka >= $daftarMateriIds->count();
    }

    /**
     * Kembalikan daftar langkah belajar dinamis (Pretest, Bab terbit, Posttest)
     * masing-masing dengan status terkunci, terbuka, atau selesai untuk stepper.
     */
    public function daftarLangkahBelajar(User $user): array
    {
        $langkah = [];

        // 1. Pretest
        $pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        if ($pretest) {
            $selesai = $this->apakahPretestSelesai($user);
            $langkah[] = [
                'tipe' => 'pretest',
                'judul' => $pretest->judul,
                'model' => $pretest,
                'status' => $selesai ? 'selesai' : 'terbuka',
            ];
        }

        // 2. Bab-bab terbit dinamis
        $babTerbit = Bab::where('status_terbit', true)->orderBy('urutan')->get();
        foreach ($babTerbit as $bab) {
            $selesai = $this->apakahBabSelesai($bab, $user);
            $terbuka = $this->apakahBabTerbuka($bab, $user);

            $status = 'terkunci';
            if ($selesai) {
                $status = 'selesai';
            } elseif ($terbuka) {
                $status = 'terbuka';
            }

            $langkah[] = [
                'tipe' => 'bab',
                'judul' => $bab->judul,
                'model' => $bab,
                'status' => $status,
            ];
        }

        // 3. Posttest
        $posttest = Kuis::where('tipe_kuis', 'posttest')->first();
        if ($posttest) {
            $selesai = PercobaanKuis::where('kuis_id', $posttest->id)
                ->where('user_id', $user->id)
                ->where('dibatalkan', false)
                ->where('status_pengerjaan', 'selesai')
                ->exists();

            $terbuka = $this->apakahKuisTerbuka($posttest, $user);

            $status = 'terkunci';
            if ($selesai) {
                $status = 'selesai';
            } elseif ($terbuka) {
                $status = 'terbuka';
            }

            $langkah[] = [
                'tipe' => 'posttest',
                'judul' => $posttest->judul,
                'model' => $posttest,
                'status' => $status,
            ];
        }

        return $langkah;
    }

    /**
     * Cek apakah pretest sudah diselesaikan oleh siswa.
     */
    protected function apakahPretestSelesai(User $user): bool
    {
        $pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        if (! $pretest) {
            return true;
        }

        return PercobaanKuis::where('kuis_id', $pretest->id)
            ->where('user_id', $user->id)
            ->where('dibatalkan', false)
            ->where('status_pengerjaan', 'selesai')
            ->exists();
    }
}
