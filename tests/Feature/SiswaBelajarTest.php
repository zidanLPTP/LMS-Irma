<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\PercobaanKuis;
use App\Models\ProgresMateri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiswaBelajarTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Budi Siswa',
            'username' => 'budi_siswa',
            'email' => 'budi@test.com',
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Admin Irma',
            'username' => 'irma_admin',
            'email' => 'irma@test.com',
            'peran' => 'admin',
            'aktif' => true,
        ]);
    }

    public function test_tamu_diarahkan_ke_masuk_saat_buka_beranda_siswa(): void
    {
        $response = $this->get(route('siswa.beranda'));
        $response->assertRedirect(route('masuk'));
    }

    public function test_admin_tidak_bisa_masuk_beranda_siswa(): void
    {
        // Admin dibatasi oleh PeranMiddleware sehingga tidak masuk beranda siswa secara sengaja/tak sengaja
        $response = $this->actingAs($this->admin)->get(route('siswa.beranda'));
        $response->assertForbidden();
    }

    public function test_beranda_menampilkan_stepper_dengan_status_benar(): void
    {
        // Kondisi 1: Pretest ada, belum dikerjakan
        $pretest = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Kemampuan Awal',
        ]);

        $bab1 = Bab::create(['judul' => 'Bab 1 Sel', 'urutan' => 1, 'status_terbit' => true]);
        $kuisBab1 = Kuis::create([
            'bab_id' => $bab1->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 1',
            'nilai_lulus' => 65.00,
        ]);
        $soalOto = $kuisBab1->soal()->create([
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal 1',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $babDraf = Bab::create(['judul' => 'Bab 2 Draf', 'urutan' => 2, 'status_terbit' => false]);

        $bab3 = Bab::create(['judul' => 'Bab 3 Genetika', 'urutan' => 3, 'status_terbit' => true]);
        $kuisBab3 = Kuis::create([
            'bab_id' => $bab3->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 3',
            'nilai_lulus' => 65.00,
        ]);
        $kuisBab3->soal()->create([
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal Gen 1',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $posttest = Kuis::create([
            'tipe_kuis' => 'posttest',
            'judul' => 'Posttest Akhir',
        ]);

        // Kondisi 1: Pretest terbuka (tautan ada). Bab 1 dan Bab 3 terkunci (tautan TIDAK ada). Bab 2 draf tidak tampil sama sekali.
        $res1 = $this->actingAs($this->siswa)->get(route('siswa.beranda'));
        $res1->assertOk();
        $res1->assertSee('Pretest Kemampuan Awal');
        $res1->assertSee(route('siswa.kuis.show', $pretest)); // Tautan pretest terbuka ada
        $res1->assertDontSee(route('siswa.bab.show', $bab1)); // Tautan Bab 1 belum ada (terkunci)
        $res1->assertDontSee(route('siswa.bab.show', $bab3)); // Tautan Bab 3 belum ada (terkunci)
        $res1->assertDontSee('Bab 2 Draf'); // Bab draf tidak tampil
        $res1->assertSee('Terkunci');

        // Kondisi 2: Pretest selesai -> Bab 1 terbuka (tautan ada), Bab 3 tetap terkunci (tautan TIDAK ada)
        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res2 = $this->get(route('siswa.beranda'));
        $res2->assertOk();
        $res2->assertSee('Selesai');
        $res2->assertSee(route('siswa.bab.show', $bab1)); // Bab 1 terbuka: tautan tampil
        $res2->assertDontSee(route('siswa.bab.show', $bab3)); // Bab 3 masih terkunci: tautan tidak ada

        // Kondisi 3: Bab 1 lulus -> Bab 2 draf dilewati dari urutan -> Bab 3 terbuka (tautan tampil nyata)
        PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 100.00,
            'nilai_akhir' => 100.00,
            'urutan_soal_teracak' => [$soalOto->id],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res3 = $this->get(route('siswa.beranda'));
        $res3->assertOk();
        $res3->assertSee('Bab 3 Genetika');
        $res3->assertSee(route('siswa.bab.show', $bab3)); // Bukti nyata: Bab 3 terbuka memiliki tautan aktif
        $res3->assertDontSee(route('siswa.kuis.show', $posttest)); // Posttest masih terkunci: tautan tidak ada
    }

    public function test_bab_terkunci_ditolak_dan_diarahkan_ke_beranda(): void
    {
        // Ada pretest belum selesai
        Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest']);
        $bab = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);

        $response = $this->actingAs($this->siswa)->get(route('siswa.bab.show', $bab));
        $response->assertRedirect(route('siswa.beranda'));
        $response->assertSessionHas('galat');
    }

    public function test_materi_bab_lain_tidak_bisa_dibuka_lewat_penebakan_id(): void
    {
        $bab1 = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);
        $bab2 = Bab::create(['judul' => 'Bab 2', 'urutan' => 2, 'status_terbit' => true]);

        $materiBab2 = Materi::create([
            'bab_id' => $bab2->id,
            'judul' => 'Materi Bab 2',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'urutan' => 1,
        ]);

        // Coba buka materi Bab 2 menggunakan rute Bab 1 -> harus 404
        $response = $this->actingAs($this->siswa)->get(route('siswa.bab.materi.show', [$bab1, $materiBab2]));
        $response->assertNotFound();
    }

    public function test_progres_materi_dibuat_sekali_secara_idempoten(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Terbuka', 'urutan' => 1, 'status_terbit' => true]);
        $materi = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Video Pengantar',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'urutan' => 1,
        ]);

        $this->actingAs($this->siswa);

        // Buka materi pertama kali
        $this->get(route('siswa.bab.materi.show', [$bab, $materi]))->assertOk();
        $this->assertEquals(1, ProgresMateri::where('user_id', $this->siswa->id)->where('materi_id', $materi->id)->count());

        // Refresh/buka materi berkali-kali
        $this->get(route('siswa.bab.materi.show', [$bab, $materi]))->assertOk();
        $this->get(route('siswa.bab.materi.show', [$bab, $materi]))->assertOk();
        $this->assertEquals(1, ProgresMateri::where('user_id', $this->siswa->id)->where('materi_id', $materi->id)->count());
    }

    public function test_materi_youtube_memakai_domain_nocookie(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);
        $materi = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Video Nocookie',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube-nocookie.com/embed/abcdef12345',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($this->siswa)->get(route('siswa.bab.materi.show', [$bab, $materi]));
        $response->assertOk();
        $response->assertSee('https://www.youtube-nocookie.com/embed/abcdef12345');
        $response->assertDontSee('www.youtube.com/watch');
    }

    public function test_penyaji_pdf_privat_otorisasi_dan_keamanan(): void
    {
        Storage::fake('local');

        $bab = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);
        $filePdf = UploadedFile::fake()->create('modul.pdf', 500, 'application/pdf');
        $pathPdf = $filePdf->storeAs('materi', 'materi_test_privat.pdf', 'local');

        $materiPdf = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Modul PDF Sel',
            'tipe' => 'pdf',
            'berkas_pdf' => $pathPdf,
            'urutan' => 1,
        ]);

        // 1. Tamu ditolak saat akses URL PDF langsung -> redirect ke masuk
        auth()->logout();
        $resTamu = $this->get(route('materi.pdf.sajikan', $materiPdf));
        $resTamu->assertRedirect(route('masuk'));

        // 2. Siswa berhak dapat mengunduh/melihat PDF (header inline, application/pdf, Cache-Control private)
        $resSiswa = $this->actingAs($this->siswa)->get(route('materi.pdf.sajikan', $materiPdf));
        $resSiswa->assertOk();
        $resSiswa->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $resSiswa->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', $resSiswa->headers->get('Cache-Control'));

        // 3. Admin juga berhak mengakses untuk pratinjau materi
        $resAdmin = $this->actingAs($this->admin)->get(route('materi.pdf.sajikan', $materiPdf));
        $resAdmin->assertOk();

        // 4. Jika bab disembunyikan (draf), siswa ditolak (403)
        $bab->update(['status_terbit' => false]);
        $resDraf = $this->actingAs($this->siswa)->get(route('materi.pdf.sajikan', $materiPdf));
        $resDraf->assertForbidden();

        // 5. Jika bab terkunci bagi siswa, akses ditolak (403)
        $bab->update(['status_terbit' => true]);
        Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest Aktif']); // belum dikerjakan -> bab terkunci
        $resTerkunci = $this->actingAs($this->siswa)->get(route('materi.pdf.sajikan', $materiPdf));
        $resTerkunci->assertForbidden();
    }
}
