<?php

namespace Database\Seeders;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\Materi;
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

        // 2. Buat 3 Siswa Contoh
        $siswaData = [
            ['nama_lengkap' => 'Ahmad Fauzi', 'username' => 'ahmad_fauzi', 'email' => 'ahmad@siswa.test', 'kelas' => 'XI MIPA 1'],
            ['nama_lengkap' => 'Siti Rahmawati', 'username' => 'siti_rahma', 'email' => 'siti@siswa.test', 'kelas' => 'XI MIPA 1'],
            ['nama_lengkap' => 'Budi Pratama', 'username' => 'budi_pratama', 'email' => 'budi@siswa.test', 'kelas' => 'XI MIPA 2'],
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
                    'aktif' => true,
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
    }
}
