<?php

namespace Database\Seeders;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seeder data demo untuk lingkungan lokal (local).
     * Berisi: 1 pretest, 2 bab dengan materi video YouTube dan contoh slide,
     * kuis dengan 3 tipe soal, 1 posttest, dan 3 siswa contoh.
     */
    public function run(): void
    {
        // Tolak berjalan bila berada di lingkungan produksi
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan di lingkungan produksi!');

            return;
        }

        // 1. Buat Berkas PDF Contoh 3 Halaman Biologi Bebas Hak Cipta bila belum ada
        $pdfPathRelatif = 'materi/modul_biologi_sel_contoh.pdf';
        $pdfPathLengkap = storage_path('app/'.$pdfPathRelatif);
        if (! file_exists($pdfPathLengkap)) {
            if (! is_dir(dirname($pdfPathLengkap))) {
                mkdir(dirname($pdfPathLengkap), 0777, true);
            }
            $header = "%PDF-1.4\n";
            $objects = [];
            $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
            $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R 4 0 R 5 0 R] /Count 3 >>\nendobj\n";
            $objects[] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
            // Halaman 1
            $s1 = "BT /F1 18 Tf 50 750 Td (Bab 1: Struktur Sel) Tj ET\nBT /F1 12 Tf 50 710 Td (Sel adalah unit fungsional terkecil dari organisme.) Tj ET\n";
            $objects[] = '7 0 obj\n<< /Length '.strlen($s1)." >>\nstream\n".$s1."endstream\nendobj\n";
            $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 7 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n";
            // Halaman 2
            $s2 = "BT /F1 18 Tf 50 750 Td (Organel Sel) Tj ET\nBT /F1 12 Tf 50 710 Td (Mitokondria berfungsi sebagai pusat respirasi sel.) Tj ET\n";
            $objects[] = '8 0 obj\n<< /Length '.strlen($s2)." >>\nstream\n".$s2."endstream\nendobj\n";
            $objects[] = "4 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 8 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n";
            // Halaman 3
            $s3 = "BT /F1 18 Tf 50 750 Td (Rangkuman Sel) Tj ET\nBT /F1 12 Tf 50 710 Td (Pelajarilah seluruh materi sebelum memulai kuis evaluasi.) Tj ET\n";
            $objects[] = '9 0 obj\n<< /Length '.strlen($s3)." >>\nstream\n".$s3."endstream\nendobj\n";
            $objects[] = "5 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 9 0 R /Resources << /Font << /F1 6 0 R >> >> >>\nendobj\n";
            $out = $header;
            $xref = [0];
            foreach ($objects as $obj) {
                $xref[] = strlen($out);
                $out .= $obj;
            }
            $xrefOffset = strlen($out);
            $count = count($xref);
            $out .= "xref\n0 $count\n0000000000 65535 f \n";
            for ($i = 1; $i < $count; $i++) {
                $out .= sprintf("%010d 00000 n \n", $xref[$i]);
            }
            $out .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";
            file_put_contents($pdfPathLengkap, $out);
        }

        // 2. Buat Siswa Contoh (termasuk nonaktif, nama kembar, dan tanpa data)
        $siswaData = [
            ['nama_lengkap' => 'Ahmad Fauzi', 'username' => 'ahmad_fauzi', 'email' => 'ahmad@siswa.test', 'kelas' => 'XI MIPA 1', 'aktif' => true],
            ['nama_lengkap' => 'Siti Rahmawati', 'username' => 'siti_rahma', 'email' => 'siti@siswa.test', 'kelas' => 'XI MIPA 1', 'aktif' => true],
            ['nama_lengkap' => 'Budi Pratama', 'username' => 'budi_pratama', 'email' => 'budi@siswa.test', 'kelas' => 'XI MIPA 2', 'aktif' => true],
            // Siswa demo nonaktif
            ['nama_lengkap' => 'Doni Saputra', 'username' => 'doni_nonaktif', 'email' => 'doni@siswa.test', 'kelas' => 'XI MIPA 2', 'aktif' => false],
            // Dua siswa dengan nama kembar (uji fitur akun ganda)
            ['nama_lengkap' => 'Rina Wijaya', 'username' => 'rina_wijaya_1', 'email' => 'rina1@siswa.test', 'kelas' => 'XI MIPA 3', 'aktif' => true],
            ['nama_lengkap' => 'rina wijaya', 'username' => 'rina_wijaya_2', 'email' => 'rina2@siswa.test', 'kelas' => 'XI MIPA 3', 'aktif' => true],
            // Siswa baru tanpa data apapun (bisa dihapus)
            ['nama_lengkap' => 'Eko Prasetyo', 'username' => 'eko_kosong', 'email' => 'eko@siswa.test', 'kelas' => 'XI MIPA 3', 'aktif' => true],
        ];

        foreach ($siswaData as $item) {
            User::updateOrCreate(
                ['username' => $item['username']],
                [
                    'nama_lengkap' => $item['nama_lengkap'],
                    'email' => $item['email'],
                    'password' => Hash::make('password123'),
                    'peran' => 'siswa',
                    'kelas' => $item['kelas'],
                    'aktif' => $item['aktif'],
                    'wajib_ganti_password' => false,
                ]
            );
        }

        // 3. Buat Pretest (Diagnosis Awal)
        $pretest = Kuis::updateOrCreate(
            ['tipe_kuis' => 'pretest'],
            [
                'judul' => 'Pretest Diagnosis Awal Biologi Sel',
                'batas_waktu_per_soal' => null, // default pengaturan
                'nilai_lulus' => null,
                'maksimal_kesempatan' => 1,
                'acak_soal' => true,
            ]
        );

        if ($pretest->soal()->count() === 0) {
            $soalPre1 = Soal::create([
                'kuis_id' => $pretest->id,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => 'Unit struktural dan fungsional terkecil dari makhluk hidup disebut...',
                'bobot_poin' => 10,
                'urutan' => 1,
            ]);
            PilihanJawaban::create(['soal_id' => $soalPre1->id, 'teks_pilihan' => 'Sel', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soalPre1->id, 'teks_pilihan' => 'Jaringan', 'apakah_benar' => false, 'urutan' => 2]);
            PilihanJawaban::create(['soal_id' => $soalPre1->id, 'teks_pilihan' => 'Organ', 'apakah_benar' => false, 'urutan' => 3]);
            PilihanJawaban::create(['soal_id' => $soalPre1->id, 'teks_pilihan' => 'Sistem Organ', 'apakah_benar' => false, 'urutan' => 4]);

            $soalPre2 = Soal::create([
                'kuis_id' => $pretest->id,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => 'Organel sel yang berfungsi sebagai penghasil energi (respirasi seluler) adalah...',
                'bobot_poin' => 10,
                'urutan' => 2,
            ]);
            PilihanJawaban::create(['soal_id' => $soalPre2->id, 'teks_pilihan' => 'Mitokondria', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soalPre2->id, 'teks_pilihan' => 'Ribosom', 'apakah_benar' => false, 'urutan' => 2]);
            PilihanJawaban::create(['soal_id' => $soalPre2->id, 'teks_pilihan' => 'Badan Golgi', 'apakah_benar' => false, 'urutan' => 3]);
            PilihanJawaban::create(['soal_id' => $soalPre2->id, 'teks_pilihan' => 'Lisosom', 'apakah_benar' => false, 'urutan' => 4]);

            Soal::create([
                'kuis_id' => $pretest->id,
                'tipe_soal' => 'esai',
                'pertanyaan' => 'Jelaskan perbedaan mendasar antara sel hewan dan sel tumbuhan beserta contoh organel khususnya!',
                'bobot_poin' => 20,
                'urutan' => 3,
            ]);
        }

        // 4. Buat Bab 1
        $bab1 = Bab::updateOrCreate(
            ['urutan' => 1],
            [
                'judul' => 'Bab 1: Struktur dan Organel Sel',
                'deskripsi' => 'Mempelajari perbedaan sel prokariotik dan eukariotik serta fungsi organel sel.',
                'status_terbit' => true,
            ]
        );

        // Materi Bab 1: Dokumen PDF Contoh 3 Halaman
        Materi::updateOrCreate(
            ['bab_id' => $bab1->id, 'urutan' => 1],
            [
                'judul' => 'Modul Bacaan: Struktur dan Organel Sel',
                'tipe' => 'pdf',
                'berkas_pdf' => $pdfPathRelatif,
            ]
        );

        // Materi Bab 1: Video YouTube
        Materi::updateOrCreate(
            ['bab_id' => $bab1->id, 'urutan' => 2],
            [
                'judul' => 'Video Pengantar Sel dan Mikroskop',
                'tipe' => 'youtube',
                'url_youtube' => 'https://www.youtube-nocookie.com/embed/URUJD5NEXC8',
            ]
        );

        // Kuis Bab 1 (Berisi ketiga tipe soal: PG, Isian, Esai)
        $kuisBab1 = Kuis::updateOrCreate(
            ['bab_id' => $bab1->id, 'tipe_kuis' => 'kuis_bab'],
            [
                'judul' => 'Kuis Bab 1: Evaluasi Pemahaman Sel',
                'batas_waktu_per_soal' => 60,
                'nilai_lulus' => 65.00,
                'maksimal_kesempatan' => 2,
                'acak_soal' => true,
            ]
        );

        if ($kuisBab1->soal()->count() === 0) {
            // PG
            $soalPg = Soal::create([
                'kuis_id' => $kuisBab1->id,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => 'Organel sel yang berfungsi sebagai tempat pembentukan energi ATP adalah...',
                'bobot_poin' => 5,
                'urutan' => 1,
            ]);
            PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Mitokondria', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Ribosom', 'apakah_benar' => false, 'urutan' => 2]);
            PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Badan Golgi', 'apakah_benar' => false, 'urutan' => 3]);
            PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Lisosom', 'apakah_benar' => false, 'urutan' => 4]);

            // Isian
            $soalIsian = Soal::create([
                'kuis_id' => $kuisBab1->id,
                'tipe_soal' => 'isian',
                'pertanyaan' => 'Klorofil terdapat di dalam organel yang bernama...',
                'bobot_poin' => 5,
                'urutan' => 2,
            ]);
            PilihanJawaban::create(['soal_id' => $soalIsian->id, 'teks_pilihan' => 'kloroplas', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soalIsian->id, 'teks_pilihan' => 'plastida', 'apakah_benar' => true, 'urutan' => 2]);

            // Esai
            Soal::create([
                'kuis_id' => $kuisBab1->id,
                'tipe_soal' => 'esai',
                'pertanyaan' => 'Jelaskan perbedaan utama antara sel hewan dan sel tumbuhan beserta contoh organel pembedanya!',
                'bobot_poin' => 10,
                'urutan' => 3,
            ]);
        }

        // 4. Buat Bab 2
        $bab2 = Bab::updateOrCreate(
            ['urutan' => 2],
            [
                'judul' => 'Bab 2: Transpor Melalui Membran Sel',
                'deskripsi' => 'Difusi, osmosis, transpor aktif, endositosis, dan eksositosis.',
                'status_terbit' => true,
            ]
        );

        Materi::updateOrCreate(
            ['bab_id' => $bab2->id, 'urutan' => 1],
            [
                'judul' => 'Video Animasi Mekanisme Transpor Membran',
                'tipe' => 'youtube',
                'url_youtube' => 'https://www.youtube-nocookie.com/embed/dPKvHrD1eS4',
            ]
        );

        $kuisBab2 = Kuis::updateOrCreate(
            ['bab_id' => $bab2->id, 'tipe_kuis' => 'kuis_bab'],
            [
                'judul' => 'Kuis Bab 2: Transpor Membran',
                'batas_waktu_per_soal' => 60,
                'nilai_lulus' => 65.00,
                'maksimal_kesempatan' => 2,
                'acak_soal' => true,
            ]
        );

        if ($kuisBab2->soal()->count() === 0) {
            $soal2 = Soal::create([
                'kuis_id' => $kuisBab2->id,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => 'Perpindahan molekul air melintasi membran semipermeabel dari larutan hipotonik ke hipertonik disebut...',
                'bobot_poin' => 10,
                'urutan' => 1,
            ]);
            PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'Osmosis', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'Difusi terfasilitasi', 'apakah_benar' => false, 'urutan' => 2]);
            PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'Endositosis', 'apakah_benar' => false, 'urutan' => 3]);
        }

        // 5. Buat Posttest (Evaluasi Akhir Penelitian)
        $posttest = Kuis::updateOrCreate(
            ['tipe_kuis' => 'posttest'],
            [
                'judul' => 'Posttest Akhir Penelitian Biologi Sel',
                'batas_waktu_per_soal' => null,
                'nilai_lulus' => null,
                'maksimal_kesempatan' => 1,
                'acak_soal' => true,
            ]
        );

        if ($posttest->soal()->count() === 0) {
            $soalPost = Soal::create([
                'kuis_id' => $posttest->id,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => 'Tahapan pembelahan sel di mana kromosom berjejer di bidang ekuator adalah...',
                'bobot_poin' => 10,
                'urutan' => 1,
            ]);
            PilihanJawaban::create(['soal_id' => $soalPost->id, 'teks_pilihan' => 'Metafase', 'apakah_benar' => true, 'urutan' => 1]);
            PilihanJawaban::create(['soal_id' => $soalPost->id, 'teks_pilihan' => 'Profase', 'apakah_benar' => false, 'urutan' => 2]);
            PilihanJawaban::create(['soal_id' => $soalPost->id, 'teks_pilihan' => 'Anafase', 'apakah_benar' => false, 'urutan' => 3]);
            PilihanJawaban::create(['soal_id' => $soalPost->id, 'teks_pilihan' => 'Telofase', 'apakah_benar' => false, 'urutan' => 4]);
        }

        // 6. Buat 2 Percobaan Demo dengan Esai Menunggu Penilaian Guru
        $soalEsaiBab1 = Soal::where('kuis_id', $kuisBab1->id)->where('tipe_soal', 'esai')->first();
        $soalPgBab1 = Soal::where('kuis_id', $kuisBab1->id)->where('tipe_soal', 'pilihan_ganda')->first();
        $pilihanBenarPg = $soalPgBab1?->pilihanJawaban()->where('apakah_benar', true)->first();

        $siswaAhmad = User::where('username', 'ahmad_fauzi')->first();
        $siswaSiti = User::where('username', 'siti_rahma')->first();

        if ($soalEsaiBab1 && $siswaAhmad) {
            $percobaanAhmad = PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $kuisBab1->id,
                    'user_id' => $siswaAhmad->id,
                    'nomor_percobaan' => 1,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => 'lulus',
                    'esai_menunggu_nilai' => true,
                    'nilai_otomatis' => 100.00,
                    'nilai_akhir' => null,
                    'dibatalkan' => false,
                    'urutan_soal_teracak' => [$soalPgBab1?->id, $soalEsaiBab1->id],
                    'waktu_mulai' => now()->subHours(2),
                    'waktu_selesai' => now()->subHours(2)->addMinutes(10),
                ]
            );

            if ($soalPgBab1 && $pilihanBenarPg) {
                JawabanSiswa::updateOrCreate(
                    ['percobaan_kuis_id' => $percobaanAhmad->id, 'soal_id' => $soalPgBab1->id],
                    [
                        'pilihan_jawaban_id' => $pilihanBenarPg->id,
                        'poin_diperoleh' => $soalPgBab1->bobot_poin,
                        'waktu_mulai_soal' => now()->subHours(2),
                        'waktu_jawab' => now()->subHours(2)->addSeconds(30),
                    ]
                );
            }

            JawabanSiswa::updateOrCreate(
                ['percobaan_kuis_id' => $percobaanAhmad->id, 'soal_id' => $soalEsaiBab1->id],
                [
                    'jawaban_teks' => 'Sel hewan tidak memiliki dinding sel dan kloroplas, bentuknya fleksibel dan memiliki sentriol. Sedangkan sel tumbuhan memiliki dinding sel dari selulosa, plastida/kloroplas untuk fotosintesis, dan vakuola sentral yang berukuran besar.',
                    'poin_diperoleh' => 0.00,
                    'catatan_guru' => null,
                    'dinilai_pada' => null,
                    'waktu_mulai_soal' => now()->subHours(2)->addMinutes(2),
                    'waktu_jawab' => now()->subHours(2)->addMinutes(9),
                ]
            );
        }

        if ($soalEsaiBab1 && $siswaSiti) {
            $percobaanSiti = PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $kuisBab1->id,
                    'user_id' => $siswaSiti->id,
                    'nomor_percobaan' => 1,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => 'lulus',
                    'esai_menunggu_nilai' => true,
                    'nilai_otomatis' => 100.00,
                    'nilai_akhir' => null,
                    'dibatalkan' => false,
                    'urutan_soal_teracak' => [$soalPgBab1?->id, $soalEsaiBab1->id],
                    'waktu_mulai' => now()->subHour(),
                    'waktu_selesai' => now()->subHour()->addMinutes(8),
                ]
            );

            if ($soalPgBab1 && $pilihanBenarPg) {
                JawabanSiswa::updateOrCreate(
                    ['percobaan_kuis_id' => $percobaanSiti->id, 'soal_id' => $soalPgBab1->id],
                    [
                        'pilihan_jawaban_id' => $pilihanBenarPg->id,
                        'poin_diperoleh' => $soalPgBab1->bobot_poin,
                        'waktu_mulai_soal' => now()->subHour(),
                        'waktu_jawab' => now()->subHour()->addSeconds(25),
                    ]
                );
            }

            JawabanSiswa::updateOrCreate(
                ['percobaan_kuis_id' => $percobaanSiti->id, 'soal_id' => $soalEsaiBab1->id],
                [
                    'jawaban_teks' => "Perbedaan sel hewan dan tumbuhan:\n1. Dinding sel: tumbuhan ada, hewan tidak ada.\n2. Kloroplas: tumbuhan punya kloroplas untuk fotosintesis.\n3. Vakuola: tumbuhan vakuola besar, sel hewan kecil atau tidak ada.",
                    'poin_diperoleh' => 0.00,
                    'catatan_guru' => null,
                    'dinilai_pada' => null,
                    'waktu_mulai_soal' => now()->subHour()->addMinutes(1),
                    'waktu_jawab' => now()->subHour()->addMinutes(7),
                ]
            );
        }

        // Variasi percobaan tambahan untuk Ahmad Fauzi (menampilkan status beragam di Nilai Saya):
        // 1. Percobaan Pretest: Selesai dengan nilai 70
        if ($siswaAhmad && isset($pretest)) {
            PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $pretest->id,
                    'user_id' => $siswaAhmad->id,
                    'nomor_percobaan' => 1,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => null, // Pretest tidak memiliki status kelulusan
                    'esai_menunggu_nilai' => false,
                    'nilai_otomatis' => 70.00,
                    'nilai_akhir' => 70.00,
                    'dibatalkan' => false,
                    'urutan_soal_teracak' => [],
                    'waktu_mulai' => now()->subDays(2),
                    'waktu_selesai' => now()->subDays(2)->addMinutes(15),
                ]
            );
        }

        // 2. Kuis Bab 2:
        // - Percobaan 1: Belum lulus (nilai 50)
        // - Percobaan 2: Lulus dengan esai yang sudah dinilai dan memiliki catatan guru (nilai 85)
        // - Percobaan 3: Dibatalkan oleh admin (tidak boleh tampil di halaman siswa)
        if ($siswaAhmad && isset($kuisBab2)) {
            // Percobaan 1 Bab 2 (Belum lulus)
            PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $kuisBab2->id,
                    'user_id' => $siswaAhmad->id,
                    'nomor_percobaan' => 1,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => 'belum_lulus',
                    'esai_menunggu_nilai' => false,
                    'nilai_otomatis' => 50.00,
                    'nilai_akhir' => 50.00,
                    'dibatalkan' => false,
                    'urutan_soal_teracak' => [],
                    'waktu_mulai' => now()->subDay(),
                    'waktu_selesai' => now()->subDay()->addMinutes(10),
                ]
            );

            // Percobaan 2 Bab 2 (Lulus, esai dinilai ber-catatan guru)
            $percobaan2Bab2 = PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $kuisBab2->id,
                    'user_id' => $siswaAhmad->id,
                    'nomor_percobaan' => 2,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => 'lulus',
                    'esai_menunggu_nilai' => false,
                    'nilai_otomatis' => 70.00,
                    'nilai_akhir' => 85.00,
                    'dibatalkan' => false,
                    'urutan_soal_teracak' => [],
                    'waktu_mulai' => now()->subHours(5),
                    'waktu_selesai' => now()->subHours(5)->addMinutes(12),
                ]
            );

            // Berikan satu catatan guru pada jawaban percobaan ini
            $soalEsaiBab2 = Soal::where('kuis_id', $kuisBab2->id)->where('tipe_soal', 'esai')->first();
            if ($soalEsaiBab2) {
                JawabanSiswa::updateOrCreate(
                    ['percobaan_kuis_id' => $percobaan2Bab2->id, 'soal_id' => $soalEsaiBab2->id],
                    [
                        'jawaban_teks' => 'Sistem peredaran darah ganda terdiri dari peredaran darah kecil (paru-paru) dan peredaran darah besar (seluruh tubuh).',
                        'poin_diperoleh' => 15.00,
                        'catatan_guru' => 'Penjelasan sudah sangat runtut dan tepat. Pertahankan pemahaman konsep ini!',
                        'dinilai_pada' => now()->subHours(3),
                        'waktu_mulai_soal' => now()->subHours(5)->addMinutes(5),
                        'waktu_jawab' => now()->subHours(5)->addMinutes(11),
                    ]
                );
            }

            // Percobaan 3 Bab 2 (Dibatalkan oleh admin karena gangguan teknis)
            PercobaanKuis::updateOrCreate(
                [
                    'kuis_id' => $kuisBab2->id,
                    'user_id' => $siswaAhmad->id,
                    'nomor_percobaan' => 3,
                ],
                [
                    'status_pengerjaan' => 'selesai',
                    'status_kelulusan' => 'belum_lulus',
                    'esai_menunggu_nilai' => false,
                    'nilai_otomatis' => 20.00,
                    'nilai_akhir' => 20.00,
                    'dibatalkan' => true,
                    'urutan_soal_teracak' => [],
                    'waktu_mulai' => now()->subHours(2),
                    'waktu_selesai' => now()->subHours(2)->addMinutes(4),
                ]
            );
        }

        // 3. Buat Pengumuman Contoh untuk Demo
        $adminUser = User::where('peran', 'admin')->first();
        if ($adminUser) {
            // Pengumuman 1: Disematkan & Terbit
            Pengumuman::updateOrCreate(
                ['judul' => 'Jadwal Pelaksanaan Penelitian Biologi'],
                [
                    'admin_id' => $adminUser->id,
                    'isi' => "Halo semua siswa!\nPelaksanaan pembelajaran mandiri biologi sel berlangsung mulai minggu ini. Pastikan Anda menyelesaikan Pretest terlebih dahulu sebelum mempelajari modul Bab 1.\nSelamat belajar dan semoga sukses!",
                    'status' => 'terbit',
                    'disematkan' => true,
                    'tanggal_kedaluwarsa' => null,
                ]
            );

            // Pengumuman 2: Draf (hanya tampil di admin)
            Pengumuman::updateOrCreate(
                ['judul' => 'Draf Petunjuk Pengisian Posttest'],
                [
                    'admin_id' => $adminUser->id,
                    'isi' => "Petunjuk teknis pengerjaan evaluasi akhir Posttest untuk seluruh peserta penelitian.\n(Masih dalam peninjauan).",
                    'status' => 'draf',
                    'disematkan' => false,
                    'tanggal_kedaluwarsa' => null,
                ]
            );

            // Pengumuman 3: Kedaluwarsa (tidak tampil di siswa)
            Pengumuman::updateOrCreate(
                ['judul' => 'Uji Coba Sistem Penelitian (Kedaluwarsa)'],
                [
                    'admin_id' => $adminUser->id,
                    'isi' => 'Uji coba server lokal telah selesai dilakukan pada hari kemarin.',
                    'status' => 'terbit',
                    'disematkan' => false,
                    'tanggal_kedaluwarsa' => now()->subDays(2)->toDateString(),
                ]
            );
        }

        // 4. Buat Notifikasi Contoh untuk Siswa Demo (Ahmad Fauzi)
        if ($siswaAhmad) {
            // Notifikasi 1: Langkah baru terbuka
            Notifikasi::updateOrCreate(
                [
                    'user_id' => $siswaAhmad->id,
                    'jenis' => 'langkah_terbuka',
                    'referensi' => 'bab_1',
                ],
                [
                    'judul' => 'Tahapan Belajar Terbuka',
                    'isi' => 'Selamat! Tahapan pembelajaran Bab 1: Struktur dan Fungsi Sel kini telah terbuka untuk Anda pelajari.',
                    'tautan' => route('siswa.beranda'),
                    'dibaca_pada' => null,
                ]
            );

            // Notifikasi 2: Esai sudah dinilai
            Notifikasi::updateOrCreate(
                [
                    'user_id' => $siswaAhmad->id,
                    'jenis' => 'esai_dinilai',
                    'referensi' => 'percobaan_2',
                ],
                [
                    'judul' => 'Nilai Esai Tersedia',
                    'isi' => 'Jawaban esai Anda untuk Kuis Bab 2 telah selesai dinilai oleh guru. Silakan periksa hasil akhir evaluasi Anda.',
                    'tautan' => route('siswa.nilai.index'),
                    'dibaca_pada' => now()->subHour(),
                ]
            );
        }
    }
}
