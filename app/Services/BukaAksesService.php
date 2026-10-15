<?php

namespace App\Services;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\ProgresMateri;
use App\Models\User;
use Illuminate\Support\Collection;

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
        $hasilMassal = $this->daftarLangkahBelajarMassal([$user]);

        return $hasilMassal[$user->id] ?? [];
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

    /**
     * Hitung langkah belajar untuk banyak siswa sekaligus secara massal (Batch Eager Loading).
     * Mencegah N+1 query: mengambil bab, kuis, soal, percobaan kuis, dan progres materi
     * hanya dalam 4-5 query, lalu mengevaluasi stepper di memori dengan logika yang PERSIS SAMA.
     *
     * @param  Collection|array  $koleksiUser
     * @return array<int, array> [user_id => [langkah_belajar...]]
     */
    public function daftarLangkahBelajarMassal(mixed $koleksiUser): array
    {
        $userIds = collect($koleksiUser)->pluck('id')->filter()->unique()->values()->all();
        if (empty($userIds)) {
            return [];
        }

        // 1. Ambil kuis & bab terbit beserta relasi soal & materi
        $pretest = Kuis::where('tipe_kuis', 'pretest')->first();
        $posttest = Kuis::where('tipe_kuis', 'posttest')->first();
        $babTerbit = Bab::where('status_terbit', true)
            ->orderBy('urutan')
            ->with(['kuis.soal', 'materi'])
            ->get();

        // 2. Ambil seluruh percobaan kuis selesai & tidak dibatalkan milik kumpulan siswa ini
        $semuaKuisIds = collect();
        if ($pretest) {
            $semuaKuisIds->push($pretest->id);
        }
        if ($posttest) {
            $semuaKuisIds->push($posttest->id);
        }
        foreach ($babTerbit as $b) {
            if ($b->kuis) {
                $semuaKuisIds->push($b->kuis->id);
            }
        }

        $percobaanGroup = PercobaanKuis::whereIn('user_id', $userIds)
            ->whereIn('kuis_id', $semuaKuisIds->unique())
            ->where('dibatalkan', false)
            ->where('status_pengerjaan', 'selesai')
            ->get()
            ->groupBy('user_id');

        // 3. Ambil seluruh progres materi dibuka milik kumpulan siswa ini
        $semuaMateriIds = $babTerbit->flatMap(fn ($b) => $b->materi->pluck('id'))->unique()->values()->all();
        $progresGroup = ! empty($semuaMateriIds)
            ? ProgresMateri::whereIn('user_id', $userIds)
                ->whereIn('materi_id', $semuaMateriIds)
                ->where('sudah_dibuka', true)
                ->get()
                ->groupBy('user_id')
            : collect();

        // 4. Hitung di memori untuk tiap siswa
        $hasilSemua = [];
        foreach ($userIds as $uid) {
            $percobaanSiswa = $percobaanGroup->get($uid, collect());
            $progresSiswa = $progresGroup->get($uid, collect());

            // Helper cek pretest selesai di memori
            $pretestSelesai = true;
            if ($pretest) {
                $pretestSelesai = $percobaanSiswa->contains(fn ($p) => (int) $p->kuis_id === (int) $pretest->id);
            }

            // Helper cek bab selesai di memori
            $fnBabSelesai = function (Bab $bab) use ($percobaanSiswa, $progresSiswa): bool {
                $kuis = $bab->kuis;
                if ($kuis) {
                    $soalOtomatisAda = $kuis->soal->contains(fn ($s) => in_array($s->tipe_soal, ['pilihan_ganda', 'isian'], true));
                    if ($soalOtomatisAda) {
                        return $percobaanSiswa->contains(fn ($p) => (int) $p->kuis_id === (int) $kuis->id &&
                            in_array($p->status_kelulusan, ['lulus', 'belum_lulus_lanjut'], true)
                        );
                    }

                    $soalEsaiAda = $kuis->soal->contains(fn ($s) => $s->tipe_soal === 'esai');
                    if ($soalEsaiAda) {
                        return $percobaanSiswa->contains(fn ($p) => (int) $p->kuis_id === (int) $kuis->id);
                    }
                }

                $materiIds = $bab->materi->pluck('id');
                if ($materiIds->isEmpty()) {
                    return true;
                }
                $dibukaCount = $progresSiswa->whereIn('materi_id', $materiIds)->count();

                return $dibukaCount >= $materiIds->count();
            };

            $langkah = [];

            // A. Pretest
            if ($pretest) {
                $langkah[] = [
                    'tipe' => 'pretest',
                    'judul' => $pretest->judul,
                    'model' => $pretest,
                    'status' => $pretestSelesai ? 'selesai' : 'terbuka',
                ];
            }

            // B. Bab-bab terbit
            $babSebelumnyaSelesai = $pretestSelesai;
            foreach ($babTerbit as $idx => $bab) {
                $selesai = $fnBabSelesai($bab);
                $terbuka = $babSebelumnyaSelesai;

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

                $babSebelumnyaSelesai = $selesai;
            }

            // C. Posttest
            if ($posttest) {
                $selesai = $percobaanSiswa->contains(fn ($p) => (int) $p->kuis_id === (int) $posttest->id);
                $babTerakhir = $babTerbit->last();
                $terbuka = false;
                if ($selesai) {
                    $terbuka = false; // Jika selesai, kuis tertutup
                } else {
                    if ($babTerakhir) {
                        $terbuka = $fnBabSelesai($babTerakhir);
                    } else {
                        $terbuka = $pretestSelesai;
                    }
                }

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

            $hasilSemua[$uid] = $langkah;
        }

        return $hasilSemua;
    }
}
