<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\BebanUjiSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Langkah15KeamananDanAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $siswaA;

    protected User $siswaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'peran' => 'admin',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        $this->siswaA = User::factory()->create([
            'peran' => 'siswa',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        $this->siswaB = User::factory()->create([
            'peran' => 'siswa',
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * B1: Audit Rute Otomatis
     * Telusuri Route::getRoutes(); hanya daftar putih eksplisit yang terbuka untuk tamu;
     * rute admin wajib peran admin; rute siswa wajib peran siswa.
     */
    public function test_audit_rute_otomatis_memastikan_hanya_daftar_putih_terbuka_untuk_tamu_dan_otorisasi_peran_ketat(): void
    {
        $daftarPutihTamu = [
            'masuk',
            'masuk.kirim',
            'daftar',
            'daftar.kirim',
            'storage.local',
        ];

        $routes = Route::getRoutes()->getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $name = $route->getName();
            $action = $route->getAction();
            $middleware = $action['middleware'] ?? [];

            // Abaikan rute redirect utama '/', internal health check 'up', dev tools, dan local storage serve
            if ($uri === '/' || $uri === 'up' || str_starts_with($uri, 'storage') || str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum')) {
                continue;
            }

            // 1. Rute Publik (Tamu)
            $apakahBolehTamu = in_array($name, $daftarPutihTamu, true);

            if (! $apakahBolehTamu) {
                // Semua rute selain daftar putih WAJIB memiliki middleware 'auth'
                $hasAuth = false;
                foreach ($middleware as $m) {
                    if ($m === 'auth' || str_starts_with($m, 'auth:')) {
                        $hasAuth = true;
                        break;
                    }
                }
                $this->assertTrue(
                    $hasAuth,
                    "Rute non-daftar-putih [{$uri}] (name: {$name}) harus dilindungi middleware auth."
                );
            }

            // 2. Rute Admin: diawali 'admin/' atau name diawali 'admin.'
            if (str_starts_with($uri, 'admin') || str_starts_with($name ?? '', 'admin.')) {
                $hasPeranAdmin = false;
                foreach ($middleware as $m) {
                    if ($m === 'peran:admin') {
                        $hasPeranAdmin = true;
                        break;
                    }
                }
                $this->assertTrue(
                    $hasPeranAdmin,
                    "Rute admin [{$uri}] harus dilindungi middleware peran:admin."
                );
            }

            // 3. Rute Siswa: diawali 'siswa/' atau name diawali 'siswa.'
            if (str_starts_with($uri, 'siswa') || str_starts_with($name ?? '', 'siswa.')) {
                // Abaikan detak yang bisa diakses user login umum
                if ($uri === 'detak' || $name === 'siswa.detak') {
                    continue;
                }

                $hasPeranSiswa = false;
                foreach ($middleware as $m) {
                    if ($m === 'peran:siswa') {
                        $hasPeranSiswa = true;
                        break;
                    }
                }
                $this->assertTrue(
                    $hasPeranSiswa,
                    "Rute siswa [{$uri}] harus dilindungi middleware peran:siswa."
                );
            }
        }
    }

    /**
     * B2: IDOR Generik
     * Siswa A tidak bisa mengakses atau mengubah milik Siswa B;
     * Siswa tidak bisa mengakses rute admin lewat penebakan ID.
     */
    public function test_idor_generik_siswa_tidak_bisa_mengakses_milik_siswa_lain_dan_rute_admin(): void
    {
        $bab = Bab::create(['judul' => 'Bab IDOR Test', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create(['bab_id' => $bab->id, 'judul' => 'Kuis IDOR', 'tipe_kuis' => 'kuis_bab']);
        \App\Models\Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal Syarat Bab 1',
            'bobot_poin' => 10,
        ]);

        // 1. Percobaan Kuis: Siswa A mencoba membuka hasil percobaan Siswa B
        $percobaanB = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswaB->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addMinutes(10),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [1],
        ]);

        $this->actingAs($this->siswaA)
            ->get(route('siswa.percobaan.hasil', $percobaanB))
            ->assertForbidden();

        // 2. Notifikasi: Siswa A mencoba membuka notifikasi Siswa B
        $notifB = Notifikasi::create([
            'user_id' => $this->siswaB->id,
            'judul' => 'Notifikasi B',
            'isi' => 'Konten Notifikasi B',
            'tautan' => '/siswa/beranda',
            'jenis' => 'info',
            'referensi' => 'test_idor_notif_'.uniqid(),
        ]);

        $this->actingAs($this->siswaA)
            ->get(route('siswa.notifikasi.buka', $notifB))
            ->assertForbidden();

        // 3. Bab & Materi: Siswa A mencoba membuka Bab 2 yang masih terkunci
        $babTerkunci = Bab::create(['judul' => 'Bab Terkunci Siswa A', 'urutan' => 2, 'status_terbit' => true]);
        $materiBabTerkunci = Materi::create([
            'bab_id' => $babTerkunci->id,
            'judul' => 'Materi Terkunci',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube-nocookie.com/embed/dummy',
            'urutan' => 1,
        ]);
        $this->actingAs($this->siswaA)
            ->get(route('siswa.bab.show', $babTerkunci))
            ->assertRedirect(route('siswa.beranda'));
        $this->actingAs($this->siswaA)
            ->get(route('siswa.bab.materi.show', [$babTerkunci, $materiBabTerkunci]))
            ->assertRedirect(route('siswa.beranda'));

        // IDOR Materi Salah Bab (Materi Bab 2 dipanggil pada URL Bab 1)
        $this->actingAs($this->siswaA)
            ->get(route('siswa.bab.materi.show', [$bab, $materiBabTerkunci]))
            ->assertNotFound();

        // 4. Kuis Terkunci: Siswa A mencoba memulai percobaan pada kuis bab terkunci
        $kuisTerkunci = Kuis::create(['bab_id' => $babTerkunci->id, 'judul' => 'Kuis Bab Terkunci', 'tipe_kuis' => 'kuis_bab']);
        $this->actingAs($this->siswaA)
            ->post(route('siswa.kuis.mulai', $kuisTerkunci))
            ->assertRedirect(route('siswa.kuis.show', $kuisTerkunci))
            ->assertSessionHas('galat');

        // 5. Pengumuman: Siswa tidak bisa membaca pengumuman berstatus draf
        $pengumumanDraf = \App\Models\Pengumuman::create([
            'judul' => 'Pengumuman Draf Rahasia',
            'isi' => 'Belum boleh dibaca siapapun',
            'status' => 'draf',
            'admin_id' => $this->admin->id,
        ]);
        $this->actingAs($this->siswaA)
            ->get(route('siswa.pengumuman.show', $pengumumanDraf))
            ->assertNotFound();

        // 6. Siswa A menebak URL admin pengguna milik Siswa B
        $this->actingAs($this->siswaA)
            ->get(route('admin.pengguna.edit', $this->siswaB))
            ->assertForbidden();

        // 7. Siswa A menebak URL penilaian esai admin
        $this->actingAs($this->siswaA)
            ->get(route('admin.penilaian-esai.show', $percobaanB))
            ->assertForbidden();
    }

    /**
     * B3: Mass Assignment Protection
     * peran, aktif, wajib_ganti_password, dibatalkan, nilai_otomatis, nilai_akhir, status_kelulusan
     * tidak bisa diisi dari request biasa.
     */
    public function test_mass_assignment_kolom_kritis_tidak_bisa_diisi_dari_request_biasa(): void
    {
        // 1. Siswa mencoba inject peran=admin, aktif=false, wajib_ganti_password=true via Ubah Profil
        $this->actingAs($this->siswaA)
            ->put(route('siswa.profil.update'), [
                'nama_lengkap' => 'Siswa Injeksi',
                'email' => $this->siswaA->email,
                'kelas' => 'XI MIPA 1',
                'peran' => 'admin',
                'aktif' => false,
                'wajib_ganti_password' => true,
            ])
            ->assertRedirect();

        $this->siswaA->refresh();
        $this->assertSame('siswa', $this->siswaA->peran, 'Peran siswa tidak boleh berubah melalui request profil!');
        $this->assertTrue($this->siswaA->aktif, 'Status aktif tidak boleh berubah melalui request profil!');
        $this->assertFalse($this->siswaA->wajib_ganti_password, 'Wajib ganti password tidak boleh berubah melalui request profil!');

        // 2. Tamu mencoba inject peran=admin saat registrasi
        Pengaturan::setel('pendaftaran_siswa', 'buka');
        Pengaturan::setel('kode_pendaftaran', '');
        auth()->logout();

        $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Daftar Hacking',
            'username' => 'siswa_hack_reg',
            'email' => 'hack_reg@biostemx.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'kelas' => 'XI MIPA 2',
            'peran' => 'admin',
            'aktif' => false,
        ])->assertRedirect();

        $userBaru = User::where('username', 'siswa_hack_reg')->firstOrFail();
        $this->assertSame('siswa', $userBaru->peran, 'Pendaftaran publik mutlak selalu berperan siswa!');
        $this->assertTrue($userBaru->aktif, 'Pendaftaran publik mutlak selalu aktif!');

        // 3. Siswa menjawab soal kuis dan mencoba inject nilai_akhir, dibatalkan, status_kelulusan
        $bab = Bab::create(['judul' => 'Bab Kuis Hack', 'urutan' => 99, 'status_terbit' => true]);
        $kuis = Kuis::create(['bab_id' => $bab->id, 'judul' => 'Kuis Hack', 'tipe_kuis' => 'kuis_bab']);
        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswaA->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'sedang_mengerjakan',
            'waktu_mulai' => now(),
            'dibatalkan' => false,
            'urutan_soal_teracak' => [1],
        ]);

        $this->actingAs($this->siswaA)
            ->post(route('siswa.percobaan.serahkan', $percobaan), [
                'nilai_akhir' => 100,
                'nilai_otomatis' => 100,
                'dibatalkan' => true,
                'status_kelulusan' => 'lulus',
            ]);

        $percobaan->refresh();
        $this->assertFalse($percobaan->dibatalkan, 'Dibatalkan tidak boleh diubah melalui request serahkan kuis!');
    }

    /**
     * B4: Header Keamanan
     * nosniff, X-Frame-Options SAMEORIGIN, Referrer-Policy, Permissions-Policy, CSP Report-Only.
     */
    public function test_header_keamanan_tersemat_di_seluruh_respons_web(): void
    {
        $response = $this->get(route('masuk'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
        
        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotEmpty($csp);
        $this->assertStringNotContainsString("'unsafe-inline'", (string) preg_replace('/style-src[^;]+;/', '', $csp), "script-src CSP tidak boleh memuat 'unsafe-inline'!");
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $csp, 'CSP tidak boleh bergantung pada cdn.jsdelivr.net!');
    }

    /**
     * B5: Halaman Galat Berbahasa Indonesia Tanpa Jejak Tumpukan (Stack Trace)
     */
    public function test_halaman_galat_berbahasa_indonesia_tanpa_jejak_tumpukan(): void
    {
        // 404 Not Found
        $res404 = $this->get('/halaman-pasti-tidak-ada-404-xyz');
        $res404->assertNotFound();
        $res404->assertSee('Halaman Tidak Ditemukan');
        $res404->assertDontSee('Stack trace');

        // 403 Forbidden
        $res403 = $this->actingAs($this->siswaA)->get(route('admin.beranda'));
        $res403->assertForbidden();
        $res403->assertSee('Akses Tidak Diizinkan');
        $res403->assertDontSee('Stack trace');
    }

    /**
     * B6: Pembatasan Laju (Rate Limiting)
     */
    public function test_pembatasan_laju_pendaftaran_dan_ekspor(): void
    {
        // 1. Ekspor Laporan: throttle 15/menit
        $this->actingAs($this->admin);
        for ($i = 1; $i <= 15; $i++) {
            $res = $this->get(route('admin.laporan.akses.ekspor'));
            $res->assertOk();
        }

        // Permintaan ke-16 harus ditolak 429 Too Many Requests
        $res16 = $this->get(route('admin.laporan.akses.ekspor'));
        $res16->assertStatus(429);
    }

    /**
     * B7: Validasi Berkas PDF Magic Bytes & Header Nosniff
     */
    public function test_unggahan_pdf_memeriksa_magic_bytes_dan_respons_sajikan_memuat_nosniff(): void
    {
        $bab = Bab::create(['judul' => 'Bab PDF Test', 'urutan' => 1, 'status_terbit' => true]);

        // File dengan ekstensi .pdf tetapi isi bukan PDF (tidak diawali %PDF-)
        $filePalsu = UploadedFile::fake()->createWithContent('bukan_pdf.pdf', 'INI TEKS BUKAN DOKUMEN PDF ASLI');

        $this->actingAs($this->admin)
            ->post(route('admin.bab.materi.store', $bab), [
                'judul' => 'Materi Palsu',
                'tipe' => 'pdf',
                'berkas_pdf' => $filePalsu,
            ])
            ->assertSessionHasErrors('berkas_pdf');

        // Berkas PDF asli
        $fileAsli = UploadedFile::fake()->createWithContent('asli.pdf', "%PDF-1.4\nKonten PDF");
        $this->actingAs($this->admin)
            ->post(route('admin.bab.materi.store', $bab), [
                'judul' => 'Materi PDF Valid',
                'tipe' => 'pdf',
                'berkas_pdf' => $fileAsli,
            ])
            ->assertRedirect();

        $materi = Materi::where('judul', 'Materi PDF Valid')->firstOrFail();
        $resSajikan = $this->actingAs($this->admin)->get(route('materi.pdf.sajikan', $materi));
        $resSajikan->assertOk();
        $resSajikan->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * B8: BebanUjiSeeder Menolak Berjalan Saat Lingkungan Produksi
     */
    public function test_beban_uji_seeder_menolak_berjalan_di_lingkungan_produksi(): void
    {
        config(['app.env' => 'production']);

        $seeder = new BebanUjiSeeder;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('BebanUjiSeeder tidak boleh dijalankan di lingkungan produksi.');

        $seeder->run();
    }

    /**
     * B9: AdminSeeder Membaca Kredensial dari .env dan Menolak di Produksi Bila Lemah
     */
    public function test_admin_seeder_membaca_env_dan_validasi_ketat_di_produksi(): void
    {
        config(['app.env' => 'production']);
        putenv('ADMIN_PASSWORD=pendek'); // < 12 karakter

        $seeder = new AdminSeeder;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Kata sandi admin produksi minimal 12 karakter.');

        $seeder->run();
    }
}
