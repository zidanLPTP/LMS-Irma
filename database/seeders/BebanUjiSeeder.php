<?php

namespace Database\Seeders;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\Materi;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BebanUjiSeeder extends Seeder
{
    /**
     * Jalankan seeder beban uji: 100 siswa, 5 bab, materi, kuis, puluhan log akses & percobaan.
     * Hanya diizinkan berjalan di environment lokal (menolak di production).
     */
    public function run(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            throw new \RuntimeException('BebanUjiSeeder tidak boleh dijalankan di lingkungan produksi.');
        }

        // 1. Buat 5 Bab Pembelajaran
        $daftarBab = [];
        for ($b = 1; $b <= 5; $b++) {
            $bab = Bab::firstOrCreate(
                ['urutan' => $b],
                [
                    'judul' => "Bab {$b}: Biologi Sel dan Ekosistem Tingkat {$b}",
                    'deskripsi' => "Materi pembelajaran komprehensif bab {$b} untuk siswa SMA.",
                    'status_terbit' => true,
                ]
            );
            $daftarBab[] = $bab;

            // Buat Materi
            Materi::firstOrCreate(
                ['bab_id' => $bab->id, 'urutan' => 1],
                [
                    'judul' => "Pengantar Bab {$b}",
                    'tipe' => 'youtube',
                    'url_youtube' => 'https://www.youtube-nocookie.com/embed/sample',
                ]
            );

            // Buat Kuis Bab
            $kuis = Kuis::firstOrCreate(
                ['bab_id' => $bab->id, 'tipe_kuis' => 'kuis_bab'],
                [
                    'judul' => "Kuis Evaluasi Bab {$b}",
                    'acak_soal' => false,
                ]
            );

            // Buat Soal PG & Esai
            if ($kuis->soal()->count() === 0) {
                $soal1 = Soal::create([
                    'kuis_id' => $kuis->id,
                    'tipe_soal' => 'pilihan_ganda',
                    'pertanyaan' => "Fungsi organel utama pada sel bab {$b}?",
                    'bobot_poin' => 10,
                    'urutan' => 1,
                ]);
                PilihanJawaban::create(['soal_id' => $soal1->id, 'teks_pilihan' => 'Respirasi sel', 'apakah_benar' => true, 'urutan' => 1]);
                PilihanJawaban::create(['soal_id' => $soal1->id, 'teks_pilihan' => 'Sintesis lipid', 'apakah_benar' => false, 'urutan' => 2]);

                Soal::create([
                    'kuis_id' => $kuis->id,
                    'tipe_soal' => 'esai',
                    'pertanyaan' => "Jelaskan proses metabolisme yang terjadi pada bab {$b}!",
                    'bobot_poin' => 20,
                    'urutan' => 2,
                ]);
            }
        }

        // 2. Buat 100 Siswa Uji beserta log akses dan percobaan kuis
        $passwordHash = Hash::make('password123');
        $kuisPertama = Kuis::first();

        for ($s = 1; $s <= 100; $s++) {
            $username = "siswa_beban_{$s}";
            $siswa = User::firstOrCreate(
                ['username' => $username],
                [
                    'nama_lengkap' => "Siswa Simulasi {$s}",
                    'email' => "siswa_beban_{$s}@biostemx.test",
                    'password' => $passwordHash,
                    'peran' => 'siswa',
                    'kelas' => 'XI MIPA '.(($s % 4) + 1),
                    'aktif' => true,
                    'wajib_ganti_password' => false,
                ]
            );

            // Buat puluhan sesi log akses untuk tiap siswa jika belum ada
            if ($siswa->logAkses()->count() < 15) {
                $dataLog = [];
                for ($l = 1; $l <= 15; $l++) {
                    $waktuMasuk = Carbon::now()->subDays(16 - $l)->setHour(8 + ($l % 8));
                    $dataLog[] = [
                        'user_id' => $siswa->id,
                        'waktu_masuk' => $waktuMasuk,
                        'aktivitas_terakhir' => $waktuMasuk->copy()->addMinutes(30),
                        'durasi_detik' => 1800,
                        'created_at' => $waktuMasuk,
                        'updated_at' => $waktuMasuk->copy()->addMinutes(30),
                    ];
                }
                LogAkses::insert($dataLog);
            }

            // Buat percobaan kuis untuk uji laporan & antrean esai
            if ($kuisPertama && $siswa->percobaanKuis()->count() === 0) {
                PercobaanKuis::create([
                    'kuis_id' => $kuisPertama->id,
                    'user_id' => $siswa->id,
                    'nomor_percobaan' => 1,
                    'waktu_mulai' => Carbon::now()->subHours(2),
                    'waktu_selesai' => Carbon::now()->subHours(2)->addMinutes(15),
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => $s % 3 === 0 ? 'lulus' : 'belum_lulus',
                    'esai_menunggu_nilai' => $s % 2 === 0,
                    'dibatalkan' => false,
                    'nilai_otomatis' => 70.00,
                    'nilai_akhir' => $s % 2 === 0 ? null : 75.00,
                    'urutan_soal_teracak' => [1, 2],
                ]);
            }
        }
    }
}
