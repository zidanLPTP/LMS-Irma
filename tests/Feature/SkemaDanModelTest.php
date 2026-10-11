<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\Materi;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\ProgresMateri;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaDanModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * Pastikan seeder pengaturan dan admin awal berjalan dengan benar di MySQL.
     */
    public function test_seeder_pengaturan_dan_admin_tersedia(): void
    {
        $this->assertDatabaseHas('pengaturan', [
            'kunci' => 'nilai_lulus',
            'nilai' => '65.00',
        ]);

        $this->assertDatabaseHas('pengaturan', [
            'kunci' => 'batas_waktu_per_soal',
            'nilai' => '60',
        ]);

        $this->assertDatabaseHas('pengaturan', [
            'kunci' => 'maksimal_kesempatan',
            'nilai' => '2',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'peran' => 'admin',
        ]);

        $admin = User::where('username', 'admin')->first();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSiswa());
    }

    /**
     * Pastikan relasi model Bab, Materi, dan ProgresMateri bekerja dengan baik.
     */
    public function test_relasi_bab_dan_materi_serta_progres(): void
    {
        $siswa = User::factory()->create();

        $bab = Bab::create([
            'urutan' => 1,
            'judul' => 'Bab 1: Struktur Sel',
            'deskripsi' => 'Pengenalan sel dan fungsinya',
            'status_terbit' => true,
        ]);

        $materiPdf = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Slide Presentasi Sel',
            'tipe' => 'pdf',
            'berkas_pdf' => 'materi/sel.pdf',
            'urutan' => 1,
        ]);

        $materiVideo = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Video Penjelasan Sel',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube.com/watch?v=dummy',
            'urutan' => 2,
        ]);

        $this->assertCount(2, $bab->materi);

        $progres = ProgresMateri::create([
            'user_id' => $siswa->id,
            'materi_id' => $materiPdf->id,
            'sudah_dibuka' => true,
            'waktu_buka' => now(),
        ]);

        $this->assertTrue($progres->user->is($siswa));
        $this->assertTrue($progres->materi->is($materiPdf));
        $this->assertCount(1, $siswa->progresMateri);
    }

    /**
     * Pastikan relasi Kuis, Soal, PilihanJawaban, dan pembacaan pengaturan efektif.
     */
    public function test_relasi_kuis_soal_dan_pengaturan_efektif(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 1',
            'batas_waktu_per_soal' => null, // fallback ke pengaturan (60)
            'nilai_lulus' => null,           // fallback ke pengaturan (65)
            'maksimal_kesempatan' => null,   // fallback ke pengaturan (2)
        ]);

        $this->assertEquals(60, $kuis->batasWaktuEfektif());
        $this->assertEquals(65.00, $kuis->nilaiLulusEfektif());
        $this->assertEquals(2, $kuis->maksimalKesempatanEfektif());

        // Penimpa khusus pada kuis
        $kuisKhusus = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Khusus',
            'batas_waktu_per_soal' => 90,
            'nilai_lulus' => 75.00,
            'maksimal_kesempatan' => 3,
        ]);

        $this->assertEquals(90, $kuisKhusus->batasWaktuEfektif());
        $this->assertEquals(75.00, $kuisKhusus->nilaiLulusEfektif());
        $this->assertEquals(3, $kuisKhusus->maksimalKesempatanEfektif());

        // Pretest & posttest selalu maksimal 1 kali kesempatan
        $pretest = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Diagnosis Awal',
        ]);
        $this->assertEquals(1, $pretest->maksimalKesempatanEfektif());

        // Soal dan pilihan
        $soalPg = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa fungsi mitokondria?',
            'bobot_poin' => 2,
            'urutan' => 1,
        ]);

        PilihanJawaban::create([
            'soal_id' => $soalPg->id,
            'teks_pilihan' => 'Penghasil energi ATP',
            'apakah_benar' => true,
            'urutan' => 1,
        ]);

        $this->assertCount(1, $soalPg->pilihanJawaban);
        $this->assertTrue($soalPg->dinilaiOtomatis());
        $this->assertFalse($soalPg->isEsai());
    }

    /**
     * Pastikan relasi PercobaanKuis, JawabanSiswa, dan label kelulusan.
     */
    public function test_relasi_percobaan_kuis_dan_jawaban_siswa(): void
    {
        $siswa = User::factory()->create();
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Uji Coba',
        ]);

        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Pertanyaan 1',
            'bobot_poin' => 1,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'sedang_mengerjakan',
            'status_kelulusan' => null,
            'esai_menunggu_nilai' => false,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [$soal->id],
        ]);

        $jawaban = JawabanSiswa::create([
            'percobaan_kuis_id' => $percobaan->id,
            'soal_id' => $soal->id,
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now()->addSeconds(20),
            'poin_diperoleh' => 1.00,
        ]);

        $this->assertTrue($jawaban->percobaanKuis->is($percobaan));
        $this->assertCount(1, $percobaan->jawabanSiswa);

        // Uji label status
        $percobaan->status_kelulusan = 'lulus';
        $this->assertEquals('Lulus', $percobaan->labelStatusKelulusan());

        $percobaan->esai_menunggu_nilai = true;
        $this->assertEquals('menunggu penilaian esai', $percobaan->labelStatusKelulusan());
    }

    /**
     * Pastikan relasi LogAkses minimalis bekerja.
     */
    public function test_log_akses_dapat_disimpan_dan_dihubungkan_ke_user(): void
    {
        $siswa = User::factory()->create();

        $log = LogAkses::create([
            'user_id' => $siswa->id,
            'waktu_masuk' => now()->subMinutes(15),
            'aktivitas_terakhir' => now(),
            'durasi_detik' => 900,
        ]);

        $this->assertTrue($log->user->is($siswa));
        $this->assertCount(1, $siswa->logAkses);
    }

    /**
     * Pastikan skenario reset kesempatan oleh admin tidak melanggar indeks unik
     * dan nomor_percobaan bertambah secara aman (A: Perbaiki bug reset kesempatan).
     */
    public function test_reset_kesempatan_oleh_admin_dan_pembuatan_percobaan_baru(): void
    {
        $siswa = User::factory()->create();
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 2',
        ]);

        // Percobaan 1 dimulai oleh siswa
        $nomorAwal = PercobaanKuis::nomorBerikutnya($kuis->id, $siswa->id);
        $this->assertEquals(1, $nomorAwal);

        $percobaan1 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => $nomorAwal,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus',
            'dibatalkan' => false,
            'nilai_otomatis' => 50.00,
            'urutan_soal_teracak' => [1],
        ]);

        $this->assertEquals(1, PercobaanKuis::hitungKesempatanTerpakai($kuis->id, $siswa->id));

        // Admin mereset kesempatan (menandai dibatalkan = true tanpa menghapus data)
        $percobaan1->update(['dibatalkan' => true]);

        // Kesempatan terpakai kembali menjadi 0
        $this->assertEquals(0, PercobaanKuis::hitungKesempatanTerpakai($kuis->id, $siswa->id));

        // Nomor percobaan baru berikutnya harus 2 (tertinggi yang ada + 1) untuk mencegah tabrakan unique index
        $nomorBerikutnya = PercobaanKuis::nomorBerikutnya($kuis->id, $siswa->id);
        $this->assertEquals(2, $nomorBerikutnya);

        // Siswa membuat percobaan baru
        $percobaan2 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => $nomorBerikutnya,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'sedang_mengerjakan',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [1],
        ]);

        $this->assertDatabaseHas('percobaan_kuis', [
            'id' => $percobaan1->id,
            'nomor_percobaan' => 1,
            'dibatalkan' => 1,
        ]);

        $this->assertDatabaseHas('percobaan_kuis', [
            'id' => $percobaan2->id,
            'nomor_percobaan' => 2,
            'dibatalkan' => 0,
        ]);

        $this->assertEquals(1, PercobaanKuis::hitungKesempatanTerpakai($kuis->id, $siswa->id));
    }

    /**
     * Pastikan hanya ada satu pretest dan satu posttest yang bisa dibuat di database (level database).
     */
    public function test_hanya_ada_satu_pretest_dan_satu_posttest(): void
    {
        Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Pertama',
        ]);

        // Mencoba membuat pretest kedua harus gagal karena constraint unik virtual column
        $this->expectException(\Throwable::class);

        Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Kedua Gagal',
        ]);
    }

    /**
     * Pastikan validasi level aplikasi melempar DomainException jika ada pretest ganda.
     */
    public function test_validasi_level_aplikasi_menolak_pretest_ganda(): void
    {
        Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Pertama',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Hanya boleh ada satu pretest di dalam sistem.');

        $kuisGanda = new Kuis([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Kedua',
        ]);
        $kuisGanda->save();
    }
}
