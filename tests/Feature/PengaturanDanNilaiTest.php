<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Models\Soal;
use App\Models\User;
use App\Services\BukaAksesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PengaturanDanNilaiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Irma Admin',
            'username' => 'admin_irma',
            'email' => 'admin@sekolah.test',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Ahmad Siswa',
            'username' => 'ahmad_siswa',
            'email' => 'ahmad@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 1',
            'aktif' => true,
        ]);
    }

    // ==========================================
    // 1. PENGATURAN ADMIN
    // ==========================================

    public function test_halaman_pengaturan_hanya_dapat_diakses_oleh_admin(): void
    {
        // Tamu dialihkan ke login
        $this->get(route('admin.pengaturan.edit'))
            ->assertRedirect(route('masuk'));

        // Siswa dilarang (403)
        $this->actingAs($this->siswa)
            ->get(route('admin.pengaturan.edit'))
            ->assertForbidden();

        // Admin diizinkan
        $this->actingAs($this->admin)
            ->get(route('admin.pengaturan.edit'))
            ->assertOk()
            ->assertSee('Pengaturan LMS')
            ->assertSee('Kebijakan Kuis');
    }

    public function test_validasi_pengaturan_menolak_svg_dan_rentang_angka_tidak_valid(): void
    {
        // Tolak berkas SVG
        $berkasSvg = UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml');

        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Baru',
                'nilai_lulus' => 150, // di luar batas 100
                'batas_waktu_per_soal' => 5, // di bawah batas 10
                'maksimal_kesempatan' => 6, // di atas batas 5
                'logo' => $berkasSvg,
            ])
            ->assertSessionHasErrors(['nilai_lulus', 'batas_waktu_per_soal', 'maksimal_kesempatan', 'logo']);
    }

    public function test_admin_berhasil_memperbarui_pengaturan_dan_tercatat_di_riwayat(): void
    {
        $berkasPng = UploadedFile::fake()->image('logo.png', 100, 100)->size(500);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'Biologi Sel SMA 1',
                'nilai_lulus' => 70,
                'batas_waktu_per_soal' => 90,
                'maksimal_kesempatan' => 3,
                'logo' => $berkasPng,
            ]);

        $response->assertRedirect(route('admin.pengaturan.edit'));
        $response->assertSessionHas('sukses');

        // Pastikan nilai tersimpan di tabel pengaturan
        $this->assertEquals('Biologi Sel SMA 1', Pengaturan::ambil('nama_situs'));
        $this->assertEquals('70.00', Pengaturan::ambil('nilai_lulus'));
        $this->assertEquals('90', Pengaturan::ambil('batas_waktu_per_soal'));
        $this->assertEquals('3', Pengaturan::ambil('maksimal_kesempatan'));

        // Pastikan berkas tersimpan di public/unggahan/
        $logoPath = Pengaturan::ambil('logo_situs');
        $this->assertNotNull($logoPath);
        $this->assertFileExists(public_path($logoPath));

        // Bersihkan berkas setelah tes
        if (file_exists(public_path($logoPath))) {
            unlink(public_path($logoPath));
        }

        // Pastikan riwayat_pengaturan mencatat perubahannya
        $this->assertDatabaseHas('riwayat_pengaturan', [
            'admin_id' => $this->admin->id,
            'kunci' => 'nama_situs',
            'nilai_baru' => 'Biologi Sel SMA 1',
        ]);
        $this->assertDatabaseHas('riwayat_pengaturan', [
            'admin_id' => $this->admin->id,
            'kunci' => 'nilai_lulus',
            'nilai_baru' => '70.00',
        ]);
    }

    public function test_perubahan_nilai_lulus_baru_tidak_mengubah_status_kelulusan_lama(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Bab 1',
            'tipe_kuis' => 'kuis_bab',
            'nilai_lulus' => null, // mengikuti pengaturan sistem (default 65)
            'maksimal_kesempatan' => 2,
            'acak_soal' => false,
        ]);

        // Siswa mengerjakan kuis dengan nilai 65 (lulus pada aturan lama)
        $percobaanLama = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 65.00,
            'nilai_akhir' => 65.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subHours(1),
        ]);

        // Admin menaikkan batas kelulusan menjadi 75
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Penelitian',
                'nilai_lulus' => 75,
                'batas_waktu_per_soal' => 60,
                'maksimal_kesempatan' => 2,
            ]);

        // Status kelulusan percobaan lama tetap 'lulus' (tidak dihitung ulang secara retroaktif)
        $percobaanLama->refresh();
        $this->assertEquals('lulus', $percobaanLama->status_kelulusan);
        $this->assertEquals(65.00, $percobaanLama->nilai_akhir);
    }

    public function test_kuis_dengan_nilai_penimpa_tidak_terpengaruh_perubahan_pengaturan_sistem(): void
    {
        $bab = Bab::create(['judul' => 'Bab Khusus', 'urutan' => 2, 'status_terbit' => true]);
        $kuisPenimpa = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Bab Standar Tinggi',
            'tipe_kuis' => 'kuis_bab',
            'nilai_lulus' => 80.00, // Penimpa spesifik
            'batas_waktu_per_soal' => 45, // Penimpa spesifik
            'maksimal_kesempatan' => 1, // Penimpa spesifik
            'acak_soal' => false,
        ]);

        // Ubah pengaturan global
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Penelitian Biologi',
                'nilai_lulus' => 60,
                'batas_waktu_per_soal' => 120,
                'maksimal_kesempatan' => 3,
            ]);

        // Kuis penimpa tetap menggunakan nilainya sendiri
        $this->assertEquals(80.00, $kuisPenimpa->nilaiLulusEfektif());
        $this->assertEquals(45, $kuisPenimpa->batasWaktuEfektif());
        $this->assertEquals(1, $kuisPenimpa->maksimalKesempatanEfektif());
    }

    public function test_pretest_dan_posttest_selalu_satu_kesempatan_walau_pengaturan_kesempatan_diubah(): void
    {
        Pengaturan::setel('maksimal_kesempatan', 4);

        $pretest = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Biologi',
            'bab_id' => null,
            'acak_soal' => false,
        ]);

        $this->assertEquals(1, $pretest->maksimalKesempatanEfektif());

        $bukaAksesService = app(BukaAksesService::class);
        $this->assertTrue($bukaAksesService->apakahKuisTerbuka($pretest, $this->siswa));

        // Buat 1 percobaan pretest selesai
        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addMinutes(10),
        ]);

        // Setelah selesai, pretest otomatis tertutup untuk siswa (hanya 1 kesempatan)
        $this->assertFalse($bukaAksesService->apakahKuisTerbuka($pretest, $this->siswa));
    }

    // ==========================================
    // 2. PROFIL SISWA
    // ==========================================

    public function test_siswa_dapat_melihat_halaman_profilnya(): void
    {
        $this->actingAs($this->siswa)
            ->get(route('siswa.profil.edit'))
            ->assertOk()
            ->assertSee($this->siswa->nama_lengkap)
            ->assertSee($this->siswa->username)
            ->assertSee($this->siswa->email);
    }

    public function test_username_siswa_tidak_bisa_diubah_oleh_siswa(): void
    {
        $this->actingAs($this->siswa)
            ->put(route('siswa.profil.update'), [
                'username' => 'hacked_username',
                'nama_lengkap' => 'Nama Baru Siswa',
                'kelas' => 'XI MIPA 2',
                'email' => $this->siswa->email,
            ])
            ->assertRedirect(route('siswa.profil.edit'));

        $this->siswa->refresh();
        $this->assertEquals('ahmad_siswa', $this->siswa->username);
        $this->assertEquals('Nama Baru Siswa', $this->siswa->nama_lengkap);
    }

    public function test_mengubah_email_memerlukan_password_saat_ini_yang_benar(): void
    {
        // 1. Password salah -> ditolak
        $this->actingAs($this->siswa)
            ->put(route('siswa.profil.update'), [
                'nama_lengkap' => $this->siswa->nama_lengkap,
                'email' => 'emailbaru@siswa.test',
                'password_saat_ini' => 'password_salah',
            ])
            ->assertSessionHasErrors(['password_saat_ini']);

        $this->siswa->refresh();
        $this->assertNotEquals('emailbaru@siswa.test', $this->siswa->email);

        // 2. Password benar -> berhasil & dicatat di riwayat_profil
        $this->actingAs($this->siswa)
            ->put(route('siswa.profil.update'), [
                'nama_lengkap' => $this->siswa->nama_lengkap,
                'email' => 'emailbaru@siswa.test',
                'password_saat_ini' => 'password', // default password factory
            ])
            ->assertRedirect(route('siswa.profil.edit'))
            ->assertSessionHas('status');

        $this->siswa->refresh();
        $this->assertEquals('emailbaru@siswa.test', $this->siswa->email);

        $this->assertDatabaseHas('riwayat_profil', [
            'user_id' => $this->siswa->id,
            'kolom' => 'email',
            'nilai_lama' => 'ahmad@siswa.test',
            'nilai_baru' => 'emailbaru@siswa.test',
        ]);
    }

    // ==========================================
    // 3. HALAMAN NILAI SAYA
    // ==========================================

    public function test_halaman_nilai_saya_menampilkan_nilai_tertinggi_dan_catatan_guru(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $kuisBab = Kuis::create([
            'bab_id' => $bab->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 1 Evaluasi',
            'maksimal_kesempatan' => 2,
            'acak_soal' => false,
        ]);

        $soalEsai = Soal::create([
            'kuis_id' => $kuisBab->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Jelaskan perbedaan sel!',
            'bobot_poin' => 20,
            'urutan' => 1,
        ]);

        // Percobaan 1: nilai 50 (belum lulus)
        $p1 = PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus',
            'nilai_otomatis' => 50.00,
            'nilai_akhir' => 50.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subDay(),
            'waktu_selesai' => now()->subDay()->addMinutes(10),
        ]);

        // Percobaan 2: nilai 80 (lulus, esai diberi catatan guru)
        $p2 = PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 60.00,
            'nilai_esai' => 20.00,
            'nilai_akhir' => 80.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHours(2),
            'waktu_selesai' => now()->subHours(2)->addMinutes(10),
        ]);

        JawabanSiswa::create([
            'percobaan_kuis_id' => $p2->id,
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => 'Jawaban esai yang bagus',
            'poin_diperoleh' => 20.00,
            'catatan_guru' => 'Analisis kloroplas sangat tepat dan jelas.',
            'dinilai_pada' => now()->subHour(),
            'waktu_mulai_soal' => now()->subHours(2),
            'waktu_jawab' => now()->subHours(2)->addMinutes(5),
        ]);

        // Percobaan 3: Dibatalkan oleh admin karena kendala teknis (harus disembunyikan dari siswa)
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 3,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 10.00,
            'nilai_akhir' => 10.00,
            'dibatalkan' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subMinutes(30),
            'waktu_selesai' => now()->subMinutes(25),
        ]);

        $response = $this->actingAs($this->siswa)
            ->get(route('siswa.nilai.index'));

        $response->assertOk();
        $response->assertSee('Kuis Bab 1 Evaluasi');
        // Nilai tertinggi yang sah adalah 80
        $response->assertSee('80');
        $response->assertSee('Lulus');
        // Catatan guru tampil
        $response->assertSee('Analisis kloroplas sangat tepat dan jelas.');
        // Percobaan 3 yang dibatalkan tidak boleh muncul
        $response->assertDontSee('Percobaan ke-3');
    }

    public function test_siswa_lain_tidak_dapat_melihat_nilai_atau_profil_siswa_ini(): void
    {
        $siswaLain = User::factory()->create([
            'nama_lengkap' => 'Budi Siswa',
            'username' => 'budi_siswa',
            'email' => 'budi@siswa.test',
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        // Saat Budi membuka /siswa/nilai, dia hanya melihat nilainya sendiri (kosong)
        $this->actingAs($siswaLain)
            ->get(route('siswa.nilai.index'))
            ->assertOk()
            ->assertDontSee('Analisis kloroplas sangat tepat');

        // Budi juga melihat profilnya sendiri di /siswa/profil
        $this->actingAs($siswaLain)
            ->get(route('siswa.profil.edit'))
            ->assertOk()
            ->assertSee('budi_siswa')
            ->assertDontSee('ahmad_siswa');
    }

    public function test_navigasi_siswa_memiliki_4_menu_utama(): void
    {
        $this->actingAs($this->siswa)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Nilai Saya')
            ->assertSee('Pengumuman')
            ->assertSee('Profil')
            ->assertSee(route('siswa.nilai.index'))
            ->assertSee(route('siswa.pengumuman.index'))
            ->assertSee(route('siswa.profil.edit'));
    }

    public function test_nama_situs_tampil_di_header_dan_halaman_masuk(): void
    {
        Pengaturan::setel('nama_situs', 'LMS Biologi Inovatif');

        // Tampil di halaman masuk
        $this->get(route('masuk'))
            ->assertOk()
            ->assertSee('LMS Biologi Inovatif');

        // Tampil di header siswa
        $this->actingAs($this->siswa)
            ->get(route('siswa.beranda'))
            ->assertOk()
            ->assertSee('LMS Biologi Inovatif');
    }

    public function test_logo_valid_tersimpan_dan_berkas_lebih_dari_satu_mb_atau_bukan_gambar_ditolak(): void
    {
        // 1. Berkas bukan gambar (teks biasa bertopeng png) atau txt
        $berkasTxt = UploadedFile::fake()->create('dokumen.txt', 50, 'text/plain');
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Uji',
                'nilai_lulus' => 65,
                'batas_waktu_per_soal' => 60,
                'maksimal_kesempatan' => 2,
                'logo' => $berkasTxt,
            ])
            ->assertSessionHasErrors(['logo']);

        // 2. Berkas gambar lebih dari 1 MB (1500 KB)
        $berkasBesar = UploadedFile::fake()->image('besar.png')->size(1500);
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Uji',
                'nilai_lulus' => 65,
                'batas_waktu_per_soal' => 60,
                'maksimal_kesempatan' => 2,
                'logo' => $berkasBesar,
            ])
            ->assertSessionHasErrors(['logo']);

        // 3. Berkas valid WEBP tersimpan di public/unggahan/
        $berkasWebp = UploadedFile::fake()->image('logo.webp', 120, 120)->size(400);
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Uji',
                'nilai_lulus' => 65,
                'batas_waktu_per_soal' => 60,
                'maksimal_kesempatan' => 2,
                'logo' => $berkasWebp,
            ])
            ->assertSessionHasNoErrors();

        $path = Pengaturan::ambil('logo_situs');
        $this->assertNotNull($path);
        $this->assertFileExists(public_path($path));
        if (file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }

    public function test_riwayat_profil_tertulis_untuk_perubahan_nama_kelas_dan_email_serta_email_harus_unik(): void
    {
        // 1. Email harus unik (mencoba pakai email milik admin)
        $this->actingAs($this->siswa)
            ->put(route('siswa.profil.update'), [
                'nama_lengkap' => 'Nama Baru',
                'kelas' => 'XI MIPA 3',
                'email' => $this->admin->email,
                'password_saat_ini' => 'password',
            ])
            ->assertSessionHasErrors(['email']);

        // 2. Perubahan valid menulis riwayat_profil untuk nama, kelas, dan email
        $this->actingAs($this->siswa)
            ->put(route('siswa.profil.update'), [
                'nama_lengkap' => 'Ahmad Fauzi Baru',
                'kelas' => 'XI MIPA 4',
                'email' => 'ahmad.fauzi@siswa.test',
                'password_saat_ini' => 'password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('riwayat_profil', [
            'user_id' => $this->siswa->id,
            'kolom' => 'nama_lengkap',
            'nilai_baru' => 'Ahmad Fauzi Baru',
        ]);
        $this->assertDatabaseHas('riwayat_profil', [
            'user_id' => $this->siswa->id,
            'kolom' => 'kelas',
            'nilai_baru' => 'XI MIPA 4',
        ]);
        $this->assertDatabaseHas('riwayat_profil', [
            'user_id' => $this->siswa->id,
            'kolom' => 'email',
            'nilai_baru' => 'ahmad.fauzi@siswa.test',
        ]);
    }

    public function test_kunci_jawaban_tidak_muncul_di_html_nilai_saya_dan_pretest_posttest_tanpa_lencana_lulus_gagal(): void
    {
        // 1. Pretest selesai
        $pretest = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Siklus Sel',
            'acak_soal' => false,
        ]);
        $soalPretest = Soal::create([
            'kuis_id' => $pretest->id,
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Apa nama organel sel fotosintesis?',
            'kunci_jawaban' => ['kloroplas', 'kloroplas daun'],
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 10.00,
            'nilai_akhir' => 10.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHours(3),
            'waktu_selesai' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->siswa)
            ->get(route('siswa.nilai.index'));

        $response->assertOk();
        $response->assertSee('Pretest Siklus Sel');
        // Kunci jawaban tidak boleh bocor ke siswa di halaman Nilai Saya
        $response->assertDontSee('kloroplas daun');
        // Pretest/Posttest tidak menampilkan lencana 'Lulus' atau 'Belum Lulus' di kartu atas
        $response->assertDontSee('Lencana Lulus');
    }
}
