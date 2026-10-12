<?php

namespace App\Services;

use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\RiwayatNilaiEsai;
use App\Models\Soal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PenilaianKuisService
{
    /**
     * 1. Acak urutan soal kuis.
     * Soal PG dan isian diacak jika acak_soal true (jika false urut berdasarkan kolom urutan).
     * Soal esai SELALU diletakkan di akhir urutan.
     *
     * @return array<int> Array ID soal
     */
    public function acakUrutanSoal(Kuis $kuis): array
    {
        $soalOtomatis = $kuis->soal()
            ->whereIn('tipe_soal', ['pilihan_ganda', 'isian'])
            ->get();

        $soalEsai = $kuis->soal()
            ->where('tipe_soal', 'esai')
            ->orderBy('urutan')
            ->get();

        if ($kuis->acak_soal) {
            $daftarIdOtomatis = $soalOtomatis->shuffle()->pluck('id')->all();
        } else {
            $daftarIdOtomatis = $soalOtomatis->sortBy('urutan')->pluck('id')->values()->all();
        }

        $daftarIdEsai = $soalEsai->pluck('id')->all();

        return array_merge($daftarIdOtomatis, $daftarIdEsai);
    }

    /**
     * 2. Mulai atau lanjutkan percobaan kuis di dalam DB::transaction dengan lockForUpdate.
     */
    public function mulaiAtauLanjutPercobaan(Kuis $kuis, User $user): PercobaanKuis
    {
        return DB::transaction(function () use ($kuis, $user) {
            // Periksa apakah ada percobaan aktif yang sedang dikerjakan dan tidak dibatalkan
            $percobaanAktif = PercobaanKuis::where('kuis_id', $kuis->id)
                ->where('user_id', $user->id)
                ->where('dibatalkan', false)
                ->where('status_pengerjaan', 'sedang_mengerjakan')
                ->lockForUpdate()
                ->first();

            if ($percobaanAktif) {
                // Finalkan otomatis bila ada soal otomatis yang waktunya sudah habis saat dilanjutkan
                $this->finalkanSoalOtomatisKadaluarsa($percobaanAktif);

                return $percobaanAktif;
            }

            // Periksa izin mulai percobaan baru
            if (! $this->bisaMulaiPercobaan($kuis, $user)) {
                throw new DomainException('Batas kesempatan pengerjaan kuis ini sudah habis.');
            }

            $nomorPercobaan = PercobaanKuis::nomorBerikutnya($kuis->id, $user->id);
            $urutanSoal = $this->acakUrutanSoal($kuis);

            return PercobaanKuis::create([
                'kuis_id' => $kuis->id,
                'user_id' => $user->id,
                'nomor_percobaan' => $nomorPercobaan,
                'waktu_mulai' => now(),
                'status_pengerjaan' => 'sedang_mengerjakan',
                'status_kelulusan' => null,
                'esai_menunggu_nilai' => false,
                'dibatalkan' => false,
                'urutan_soal_teracak' => $urutanSoal,
            ]);
        });
    }

    /**
     * Finalkan soal pilihan ganda / isian yang sudah dimulai tapi waktunya sudah lewat.
     */
    public function finalkanSoalOtomatisKadaluarsa(PercobaanKuis $percobaan): void
    {
        $batasWaktu = $percobaan->kuis->batasWaktuEfektif();
        $toleransi = (int) config('lms.toleransi_timer_detik', 5);
        $sekarang = now();

        $jawabanMenggantung = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
            ->whereNull('waktu_jawab')
            ->get();

        foreach ($jawabanMenggantung as $j) {
            if ($j->soal->dinilaiOtomatis()) {
                $maksimalWaktu = $j->waktu_mulai_soal->copy()->addSeconds($batasWaktu + $toleransi);
                if ($sekarang->greaterThan($maksimalWaktu)) {
                    $j->update([
                        'pilihan_jawaban_id' => null,
                        'jawaban_teks' => null,
                        'waktu_jawab' => $maksimalWaktu,
                        'poin_diperoleh' => 0.00,
                    ]);
                }
            }
        }
    }

    /**
     * 3. Cek apakah siswa masih bisa memulai percobaan kuis baru.
     * False bila kesempatan terpakai (tidak dibatalkan) >= maksimal efektif.
     */
    public function bisaMulaiPercobaan(Kuis $kuis, User $user): bool
    {
        $maksimalEfektif = $kuis->maksimalKesempatanEfektif();
        $kesempatanTerpakai = PercobaanKuis::hitungKesempatanTerpakai($kuis->id, $user->id);

        return $kesempatanTerpakai < $maksimalEfektif;
    }

    /**
     * 4. Mulai soal tertentu dalam percobaan.
     * Tulis waktu_mulai_soal HANYA SEKALI. Refresh tidak mengulang timer.
     * Hanya soal aktif (soal pertama di urutan yang belum dijawab) yang boleh dibuka.
     */
    public function mulaiSoal(PercobaanKuis $percobaan, Soal $soal): JawabanSiswa
    {
        if ($percobaan->status_pengerjaan === 'selesai') {
            throw new DomainException('Percobaan kuis ini sudah diserahkan.');
        }

        $urutanSoal = $percobaan->urutan_soal_teracak ?? [];
        if (! in_array($soal->id, $urutanSoal, true)) {
            throw new DomainException('Soal ini bukan bagian dari percobaan kuis ini.');
        }

        // Finalkan dulu jika ada soal sebelumnya yang sudah expired
        $this->finalkanSoalOtomatisKadaluarsa($percobaan);

        // Cari jawaban yang sudah ada untuk soal ini
        $jawabanAda = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
            ->where('soal_id', $soal->id)
            ->first();

        if ($jawabanAda) {
            return $jawabanAda;
        }

        // Validasi urutan: cari soal pertama di urutan yang belum memiliki waktu_jawab
        foreach ($urutanSoal as $soalId) {
            $sudahJawab = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                ->where('soal_id', $soalId)
                ->whereNotNull('waktu_jawab')
                ->exists();

            if (! $sudahJawab) {
                if ((int) $soalId !== (int) $soal->id) {
                    throw new DomainException('Harus menyelesaikan soal sebelumnya terlebih dahulu.');
                }
                break;
            }
        }

        return JawabanSiswa::create([
            'percobaan_kuis_id' => $percobaan->id,
            'soal_id' => $soal->id,
            'waktu_mulai_soal' => now(),
            'poin_diperoleh' => 0.00,
        ]);
    }

    /**
     * 5 & 6. Simpan jawaban siswa untuk butir soal tertentu.
     */
    public function simpanJawaban(PercobaanKuis $percobaan, Soal $soal, array $data): JawabanSiswa
    {
        if ($percobaan->status_pengerjaan === 'selesai') {
            throw new DomainException('Percobaan kuis sudah selesai.');
        }

        $urutanSoal = $percobaan->urutan_soal_teracak ?? [];
        if (! in_array($soal->id, $urutanSoal, true)) {
            throw new DomainException('Soal bukan bagian dari kuis.');
        }

        $jawaban = JawabanSiswa::firstOrCreate(
            ['percobaan_kuis_id' => $percobaan->id, 'soal_id' => $soal->id],
            ['waktu_mulai_soal' => now(), 'poin_diperoleh' => 0.00]
        );

        $sekarang = now();

        // Untuk PG dan isian: jawaban pertama yang menang, pengiriman ulang ditolak
        if ($soal->dinilaiOtomatis()) {
            if (! is_null($jawaban->waktu_jawab)) {
                throw new DomainException('Jawaban untuk soal ini sudah dikirim.');
            }

            $batasWaktu = $percobaan->kuis->batasWaktuEfektif();
            $toleransi = (int) config('lms.toleransi_timer_detik', 5);
            $maksimalWaktu = $jawaban->waktu_mulai_soal->copy()->addSeconds($batasWaktu + $toleransi);

            // Validasi timer: jika waktu_jawab > batas toleransi, jawaban dikosongkan (poin 0)
            if ($sekarang->greaterThan($maksimalWaktu)) {
                $jawaban->update([
                    'pilihan_jawaban_id' => null,
                    'jawaban_teks' => null,
                    'waktu_jawab' => $sekarang,
                    'poin_diperoleh' => 0.00,
                ]);

                return $jawaban;
            }

            // Hitung poin PG / Isian
            $poin = 0.00;
            $pilihanId = $data['pilihan_jawaban_id'] ?? null;
            $teks = $data['jawaban_teks'] ?? null;

            if ($soal->tipe_soal === 'pilihan_ganda' && $pilihanId) {
                $pilihan = PilihanJawaban::find($pilihanId);
                if ($pilihan && (int) $pilihan->soal_id === (int) $soal->id && $pilihan->apakah_benar) {
                    $poin = (float) $soal->bobot_poin;
                }
            } elseif ($soal->tipe_soal === 'isian' && ! empty($teks)) {
                $teksNormal = $this->normalisasiTeksIsian($teks);
                $kunciBenar = $soal->pilihanJawaban()
                    ->where('apakah_benar', true)
                    ->pluck('teks_pilihan')
                    ->all();

                foreach ($kunciBenar as $kunci) {
                    if ($this->normalisasiTeksIsian($kunci) === $teksNormal) {
                        $poin = (float) $soal->bobot_poin;
                        break;
                    }
                }
            }

            $jawaban->update([
                'pilihan_jawaban_id' => $pilihanId,
                'jawaban_teks' => $teks,
                'waktu_jawab' => $sekarang,
                'poin_diperoleh' => $poin,
            ]);

            return $jawaban;
        }

        // Untuk Esai: bebas timer, boleh diperbarui sampai diserahkan
        $jawaban->update([
            'jawaban_teks' => $data['jawaban_teks'] ?? null,
            'waktu_jawab' => $sekarang,
        ]);

        return $jawaban;
    }

    /**
     * 7. Serahkan percobaan kuis (idempoten).
     */
    public function serahkanPercobaan(PercobaanKuis $percobaan): PercobaanKuis
    {
        if ($percobaan->status_pengerjaan === 'selesai') {
            return $percobaan;
        }

        return DB::transaction(function () use ($percobaan) {
            $kuis = $percobaan->kuis;
            $semuaSoal = $kuis->soal;

            $totalBobotOtomatis = 0;
            $totalPoinOtomatis = 0.00;
            $totalBobotSemua = 0;
            $totalPoinSemua = 0.00;
            $adaEsaiMenunggu = false;

            foreach ($semuaSoal as $soal) {
                $totalBobotSemua += $soal->bobot_poin;

                $jawaban = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                    ->where('soal_id', $soal->id)
                    ->first();

                if ($soal->dinilaiOtomatis()) {
                    $totalBobotOtomatis += $soal->bobot_poin;
                    if ($jawaban) {
                        $totalPoinOtomatis += (float) $jawaban->poin_diperoleh;
                        $totalPoinSemua += (float) $jawaban->poin_diperoleh;
                    }
                } elseif ($soal->isEsai()) {
                    $teksJawaban = trim((string) ($jawaban ? $jawaban->jawaban_teks : ''));
                    // Esai kosong: poin 0 dan dianggap sudah dinilai
                    if ($teksJawaban === '') {
                        if (! $jawaban) {
                            $jawaban = JawabanSiswa::create([
                                'percobaan_kuis_id' => $percobaan->id,
                                'soal_id' => $soal->id,
                                'waktu_mulai_soal' => now(),
                                'waktu_jawab' => now(),
                                'jawaban_teks' => null,
                                'poin_diperoleh' => 0.00,
                                'dinilai_pada' => now(),
                            ]);
                        } else {
                            $jawaban->update([
                                'poin_diperoleh' => 0.00,
                                'dinilai_pada' => now(),
                            ]);
                        }
                    } else {
                        // Esai berisi jawaban: cek apakah sudah dinilai guru
                        if (is_null($jawaban->dinilai_pada)) {
                            $adaEsaiMenunggu = true;
                        } else {
                            $totalPoinSemua += (float) $jawaban->poin_diperoleh;
                        }
                    }
                }
            }

            // Hitung nilai_otomatis
            $nilaiOtomatis = null;
            if ($totalBobotOtomatis > 0) {
                $nilaiOtomatis = round(($totalPoinOtomatis / $totalBobotOtomatis) * 100, 2);
            }

            // Hitung nilai_akhir
            $nilaiAkhir = null;
            if (! $adaEsaiMenunggu) {
                $nilaiAkhir = $totalBobotSemua > 0
                    ? round(($totalPoinSemua / $totalBobotSemua) * 100, 2)
                    : 0.00;
            }

            // Tentukan status_kelulusan (hanya untuk kuis_bab dengan soal otomatis)
            $statusKelulusan = null;
            if ($kuis->tipe_kuis === 'kuis_bab' && ! is_null($nilaiOtomatis)) {
                $nilaiLulus = $kuis->nilaiLulusEfektif();
                if ($nilaiOtomatis >= $nilaiLulus) {
                    $statusKelulusan = 'lulus';
                } else {
                    $kesempatanTerpakai = PercobaanKuis::where('kuis_id', $kuis->id)
                        ->where('user_id', $percobaan->user_id)
                        ->where('dibatalkan', false)
                        ->count();

                    $maksimalKesempatan = $kuis->maksimalKesempatanEfektif();
                    if ($kesempatanTerpakai >= $maksimalKesempatan) {
                        $statusKelulusan = 'belum_lulus_lanjut';
                    } else {
                        $statusKelulusan = 'belum_lulus';
                    }
                }
            }

            $percobaan->update([
                'status_pengerjaan' => 'selesai',
                'waktu_selesai' => now(),
                'status_kelulusan' => $statusKelulusan,
                'esai_menunggu_nilai' => $adaEsaiMenunggu,
                'nilai_otomatis' => $nilaiOtomatis,
                'nilai_akhir' => $nilaiAkhir,
            ]);

            // Picu notifikasi langkah belajar berikutnya jika terbuka (Peristiwa B)
            try {
                $user = $percobaan->user;
                $bukaAksesService = app(BukaAksesService::class);
                $daftarLangkah = $bukaAksesService->daftarLangkahBelajar($user);

                foreach ($daftarLangkah as $langkah) {
                    if ($langkah['status'] === 'terbuka') {
                        $model = $langkah['model'];
                        $tipe = $langkah['tipe'];
                        app(NotifikasiService::class)->notifikasiLangkahTerbuka(
                            $user,
                            $tipe,
                            $model->id,
                            $langkah['judul']
                        );
                    }
                }
            } catch (\Throwable $e) {
                // Notifikasi tidak boleh menggagalkan penyerahan kuis
            }

            return $percobaan->fresh();
        });
    }

    /**
     * 8. Penilaian manual esai oleh admin/guru.
     */
    public function nilaiEsai(JawabanSiswa $jawaban, float $poin, ?string $catatan = null, ?User $admin = null): JawabanSiswa
    {
        $soal = $jawaban->soal;
        if (! $soal->isEsai()) {
            throw new DomainException('Soal ini bukan tipe esai.');
        }

        if ($poin < 0 || $poin > (float) $soal->bobot_poin) {
            throw new InvalidArgumentException("Poin harus berada pada rentang 0 sampai {$soal->bobot_poin}.");
        }

        return DB::transaction(function () use ($jawaban, $poin, $catatan, $admin) {
            $poinLama = $jawaban->poin_diperoleh;
            $catatanLama = $jawaban->catatan_guru;
            $sudahPernahDinilai = ! is_null($jawaban->dinilai_pada);

            $jawaban->update([
                'poin_diperoleh' => $poin,
                'catatan_guru' => $catatan,
                'dinilai_pada' => now(),
            ]);

            // Jika ini penilaian koreksi atau dilakukan oleh admin, catat ke tabel riwayat_nilai_esai
            if ($admin || $sudahPernahDinilai) {
                RiwayatNilaiEsai::create([
                    'jawaban_siswa_id' => $jawaban->id,
                    'admin_id' => $admin ? $admin->id : auth()->id(),
                    'poin_lama' => $poinLama,
                    'poin_baru' => $poin,
                    'catatan_lama' => $catatanLama,
                    'catatan_baru' => $catatan,
                    'created_at' => now(),
                ]);
            }

            $percobaan = $jawaban->percobaanKuis;
            $kuis = $percobaan->kuis;

            // Cek apakah seluruh esai berisi sudah dinilai
            $soalEsaiIds = $kuis->soal()->where('tipe_soal', 'esai')->pluck('id');

            $adaBelumDinilai = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)
                ->whereIn('soal_id', $soalEsaiIds)
                ->whereNotNull('jawaban_teks')
                ->whereNull('dinilai_pada')
                ->exists();

            if (! $adaBelumDinilai) {
                // Hitung nilai akhir baru
                $totalBobotSemua = (float) $kuis->soal()->sum('bobot_poin');
                $totalPoinSemua = (float) JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)->sum('poin_diperoleh');

                $nilaiAkhir = $totalBobotSemua > 0
                    ? round(($totalPoinSemua / $totalBobotSemua) * 100, 2)
                    : 0.00;

                // status_kelulusan TIDAK BOLEH berubah
                $percobaan->update([
                    'esai_menunggu_nilai' => false,
                    'nilai_akhir' => $nilaiAkhir,
                ]);

                // Picu notifikasi siswa (Peristiwa A)
                try {
                    app(NotifikasiService::class)->notifikasiEsaiDinilai($percobaan->user, $percobaan->id, $kuis->judul);
                } catch (\Throwable $e) {
                    // Jangan gagalkan transaksi penilaian jika notifikasi gagal
                }
            }

            return $jawaban;
        });
    }

    /**
     * 9. Ambil nilai tertinggi dari percobaan kuis yang selesai dan tidak dibatalkan.
     */
    public function ambilNilaiTertinggi(Kuis $kuis, User $user): ?float
    {
        $semuaPercobaan = PercobaanKuis::where('kuis_id', $kuis->id)
            ->where('user_id', $user->id)
            ->where('dibatalkan', false)
            ->where('status_pengerjaan', 'selesai')
            ->get();

        if ($semuaPercobaan->isEmpty()) {
            return null;
        }

        $nilaiTertinggi = null;

        foreach ($semuaPercobaan as $percobaan) {
            $nilaiPercobaan = ! is_null($percobaan->nilai_akhir)
                ? (float) $percobaan->nilai_akhir
                : (! is_null($percobaan->nilai_otomatis) ? (float) $percobaan->nilai_otomatis : 0.00);

            if (is_null($nilaiTertinggi) || $nilaiPercobaan > $nilaiTertinggi) {
                $nilaiTertinggi = $nilaiPercobaan;
            }
        }

        return $nilaiTertinggi;
    }

    /**
     * Normalisasi teks isian: trim, lowercase, rapatkan spasi ganda di tengah.
     */
    protected function normalisasiTeksIsian(string $teks): string
    {
        $teks = mb_strtolower(trim($teks));

        return preg_replace('/\s+/', ' ', $teks) ?? $teks;
    }
}
