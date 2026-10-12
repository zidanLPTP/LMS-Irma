<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\PercobaanKuis;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DasborPengumumanNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Admin Irma',
            'username' => 'admin_irma',
            'email' => 'admin@sekolah.test',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Ahmad Fauzi',
            'username' => 'ahmad_fauzi',
            'email' => 'ahmad@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 1',
            'aktif' => true,
        ]);
    }

    // ==========================================
    // 1. DASBOR ADMIN
    // ==========================================

    public function test_hanya_admin_yang_dapat_mengakses_dasbor_admin(): void
    {
        // Tamu ke login
        $this->get(route('admin.beranda'))->assertRedirect(route('masuk'));

        // Siswa dilarang 403
        $this->actingAs($this->siswa)
            ->get(route('admin.beranda'))
            ->assertForbidden();

        // Admin berhasil
        $this->actingAs($this->admin)
            ->get(route('admin.beranda'))
            ->assertOk()
            ->assertSee('Dasbor Pengelola')
            ->assertSee('Sebaran Progres Pembelajaran');
    }

    public function test_angka_ringkasan_dasbor_akurat_dan_tidak_mengikutsertakan_admin(): void
    {
        // 1 siswa aktif lain, 1 siswa nonaktif
        User::factory()->create(['peran' => 'siswa', 'aktif' => true]);
        User::factory()->create(['peran' => 'siswa', 'aktif' => false]);

        // Catat log sesi siswa hari ini
        LogAkses::create([
            'user_id' => $this->siswa->id,
            'waktu_masuk' => now(),
            'aktivitas_terakhir' => now()->addMinutes(10),
            'durasi_detik' => 600,
        ]);

        // Log akses admin hari ini (TIDAK BOLEH dihitung di ringkasan siswa masuk / sesi)
        LogAkses::create([
            'user_id' => $this->admin->id,
            'waktu_masuk' => now(),
            'aktivitas_terakhir' => now()->addMinutes(5),
            'durasi_detik' => 300,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.beranda'));
        $response->assertOk();

        // Total siswa 3 (2 aktif, 1 nonaktif)
        $response->assertSee('2 aktif');
        $response->assertSee('1 nonaktif');
    }

    public function test_rata_rata_kuis_tidak_mengikutsertakan_nilai_null_dan_percobaan_dibatalkan(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Evaluasi Bab 1',
            'tipe_kuis' => 'kuis_bab',
            'maksimal_kesempatan' => 2,
            'acak_soal' => false,
        ]);

        $siswa2 = User::factory()->create(['peran' => 'siswa', 'aktif' => true]);

        // Percobaan Siswa 1: Selesai dengan nilai 80
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 80.00,
            'nilai_akhir' => 80.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->subMinutes(50),
        ]);

        // Percobaan Siswa 2: Menunggu esai (nilai_akhir null) -> tidak boleh dihitung
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa2->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => null,
            'nilai_otomatis' => 70.00,
            'nilai_akhir' => null,
            'esai_menunggu_nilai' => true,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subMinutes(40),
            'waktu_selesai' => now()->subMinutes(30),
        ]);

        // Percobaan Siswa 2 (Percobaan dibatalkan): nilai 100 -> tidak boleh dihitung
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa2->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 100.00,
            'nilai_akhir' => 100.00,
            'dibatalkan' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subMinutes(20),
            'waktu_selesai' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.beranda'));
        $response->assertOk();
        $response->assertSee('Kuis Evaluasi Bab 1');
        // Rata-rata hanya dari Siswa 1 yaitu 80 (bukan dirata-ratakan dengan 100 atau null)
        $response->assertSee('80');
    }

    // ==========================================
    // 2. PENGUMUMAN
    // ==========================================

    public function test_admin_dapat_mengelola_pengumuman_dan_isi_teks_biasa_ter_escape(): void
    {
        $payload = [
            'judul' => 'Pengumuman Penting Siswa',
            'isi' => "Halo semua!<script>alert('xss')</script>\nBaris kedua pengumuman.",
            'status' => 'terbit',
            'disematkan' => true,
            'tanggal_kedaluwarsa' => now()->addDays(7)->toDateString(),
        ];

        // Store
        $resStore = $this->actingAs($this->admin)->post(route('admin.pengumuman.store'), $payload);
        $resStore->assertRedirect(route('admin.pengumuman.index'));

        $pengumuman = Pengumuman::first();
        $this->assertNotNull($pengumuman);
        $this->assertEquals('Pengumuman Penting Siswa', $pengumuman->judul);
        $this->assertTrue($pengumuman->disematkan);
        // Script HTML ter-strip atau ter-escape
        $this->assertStringNotContainsString('<script>', $pengumuman->isi);

        // Siswa membuka pengumuman
        $resSiswa = $this->actingAs($this->siswa)->get(route('siswa.pengumuman.show', $pengumuman));
        $resSiswa->assertOk();
        $resSiswa->assertSee('Baris kedua pengumuman.');
        $resSiswa->assertDontSee('<script>alert', false);
    }

    public function test_pengumuman_draf_dan_kedaluwarsa_tidak_tampil_bagi_siswa(): void
    {
        // 1. Pengumuman draf
        $draf = Pengumuman::create([
            'admin_id' => $this->admin->id,
            'judul' => 'Pengumuman Draf Rahasia',
            'isi' => 'Ini adalah draf yang belum terbit.',
            'status' => 'draf',
            'disematkan' => false,
        ]);

        // 2. Pengumuman kedaluwarsa
        $kedaluwarsa = Pengumuman::create([
            'admin_id' => $this->admin->id,
            'judul' => 'Pengumuman Masa Lalu',
            'isi' => 'Sudah lewat waktunya.',
            'status' => 'terbit',
            'disematkan' => false,
            'tanggal_kedaluwarsa' => now()->subDay()->toDateString(),
        ]);

        // Siswa buka daftar pengumuman
        $response = $this->actingAs($this->siswa)->get(route('siswa.pengumuman.index'));
        $response->assertOk();
        $response->assertDontSee('Pengumuman Draf Rahasia');
        $response->assertDontSee('Pengumuman Masa Lalu');

        // Siswa coba akses detail draf secara langsung -> 404
        $this->actingAs($this->siswa)
            ->get(route('siswa.pengumuman.show', $draf))
            ->assertNotFound();

        // Siswa coba akses detail kedaluwarsa secara langsung -> 404
        $this->actingAs($this->siswa)
            ->get(route('siswa.pengumuman.show', $kedaluwarsa))
            ->assertNotFound();
    }

    public function test_pengumuman_disematkan_tampil_paling_atas_dan_pembacaan_dicatat_sekali(): void
    {
        $biasa = Pengumuman::create([
            'admin_id' => $this->admin->id,
            'judul' => 'Pengumuman Biasa Terbaru',
            'isi' => 'Isi biasa.',
            'status' => 'terbit',
            'disematkan' => false,
            'created_at' => now(),
        ]);

        $disematkan = Pengumuman::create([
            'admin_id' => $this->admin->id,
            'judul' => 'Pengumuman Penting Disematkan',
            'isi' => 'Isi penting disematkan.',
            'status' => 'terbit',
            'disematkan' => true,
            'created_at' => now()->subDay(), // Dibuat lebih dulu tapi disematkan
        ]);

        $response = $this->actingAs($this->siswa)->get(route('siswa.pengumuman.index'));
        $response->assertOk();
        // Memastikan pengumuman disematkan muncul sebelum pengumuman biasa di HTML
        $posisiSemat = strpos($response->getContent(), 'Pengumuman Penting Disematkan');
        $posisiBiasa = strpos($response->getContent(), 'Pengumuman Biasa Terbaru');
        $this->assertLessThan($posisiBiasa, $posisiSemat);

        // Siswa membaca pengumuman disematkan pertama kali
        $this->actingAs($this->siswa)->get(route('siswa.pengumuman.show', $disematkan));
        $this->assertDatabaseHas('pengumuman_dibaca', [
            'user_id' => $this->siswa->id,
            'pengumuman_id' => $disematkan->id,
        ]);
        $this->assertEquals(1, $this->siswa->pengumumanDibaca()->count());

        // Siswa membuka lagi -> idempoten (tetap 1 baris di tabel pengumuman_dibaca)
        $this->actingAs($this->siswa)->get(route('siswa.pengumuman.show', $disematkan));
        $this->assertEquals(1, $this->siswa->pengumumanDibaca()->count());
    }

    // ==========================================
    // 3. NOTIFIKASI DALAM APLIKASI
    // ==========================================

    public function test_notifikasi_terbit_pada_peristiwa_dan_tidak_ganda(): void
    {
        $service = app(NotifikasiService::class);

        // Peristiwa 1: Langkah baru terbuka
        $notif1 = $service->notifikasiLangkahTerbuka($this->siswa, 'bab', 1, 'Bab 1 Biologi');
        $this->assertNotNull($notif1);

        // Panggil lagi dengan referensi yang sama -> tidak membuat baris ganda
        $notif1Duplikat = $service->notifikasiLangkahTerbuka($this->siswa, 'bab', 1, 'Bab 1 Biologi');
        $this->assertEquals($notif1->id, $notif1Duplikat->id);
        $this->assertEquals(1, Notifikasi::where('user_id', $this->siswa->id)->count());

        // Peristiwa 2: Esai dinilai
        $notif2 = $service->notifikasiEsaiDinilai($this->siswa, 5, 'Kuis Bab 1');
        $this->assertNotNull($notif2);

        // Peristiwa 3: Kesempatan direset dengan id riwayat tindakan admin
        $notif3 = $service->notifikasiKesempatanDireset($this->siswa, 1, 'Kuis Bab 1', 101);
        $this->assertNotNull($notif3);

        $this->assertEquals(3, Notifikasi::where('user_id', $this->siswa->id)->count());
    }

    public function test_dua_reset_berbeda_pada_kuis_yang_sama_masing_masing_mengirim_notifikasi(): void
    {
        $service = app(NotifikasiService::class);

        // Reset tindakan pertama (id: 201)
        $notif1 = $service->notifikasiKesempatanDireset($this->siswa, 5, 'Kuis Bab 2', 201);
        $this->assertNotNull($notif1);

        // Reset tindakan kedua pada kuis yang sama (id: 202)
        $notif2 = $service->notifikasiKesempatanDireset($this->siswa, 5, 'Kuis Bab 2', 202);
        $this->assertNotNull($notif2);

        $this->assertNotEquals($notif1->id, $notif2->id);
        $this->assertEquals(2, Notifikasi::where('user_id', $this->siswa->id)->where('jenis', 'kesempatan_direset')->count());
    }

    public function test_halaman_pengumuman_admin_menolak_siswa_dan_tamu(): void
    {
        // Tamu ke login
        $this->get(route('admin.pengumuman.index'))
            ->assertRedirect(route('masuk'));

        // Siswa ditolak 403
        $this->actingAs($this->siswa)
            ->get(route('admin.pengumuman.index'))
            ->assertForbidden();

        $this->actingAs($this->siswa)
            ->get(route('admin.pengumuman.create'))
            ->assertForbidden();
    }

    public function test_siswa_hanya_bisa_membuka_notifikasi_miliknya_dan_menandai_terbaca(): void
    {
        $siswaLain = User::factory()->create(['peran' => 'siswa', 'aktif' => true]);

        $notifMilikSiswaLain = Notifikasi::create([
            'user_id' => $siswaLain->id,
            'jenis' => 'langkah_terbuka',
            'referensi' => 'bab_2',
            'judul' => 'Bab 2 Terbuka',
            'isi' => 'Konten untuk siswa lain',
            'tautan' => route('siswa.beranda'),
            'dibaca_pada' => null,
        ]);

        $notifSaya = Notifikasi::create([
            'user_id' => $this->siswa->id,
            'jenis' => 'langkah_terbuka',
            'referensi' => 'bab_1',
            'judul' => 'Bab 1 Terbuka',
            'isi' => 'Konten untuk saya',
            'tautan' => route('siswa.nilai.index'),
            'dibaca_pada' => null,
        ]);

        // Coba buka notifikasi milik orang lain -> 403 Forbidden
        $this->actingAs($this->siswa)
            ->get(route('siswa.notifikasi.buka', $notifMilikSiswaLain))
            ->assertForbidden();

        // Buka notifikasi milik sendiri -> tandai terbaca & dialihkan ke tautan
        $this->actingAs($this->siswa)
            ->get(route('siswa.notifikasi.buka', $notifSaya))
            ->assertRedirect(route('siswa.nilai.index'));

        $notifSaya->refresh();
        $this->assertNotNull($notifSaya->dibaca_pada);

        // Tandai semua dibaca lewat endpoint POST
        $notifSaya2 = Notifikasi::create([
            'user_id' => $this->siswa->id,
            'jenis' => 'esai_dinilai',
            'referensi' => 'percobaan_10',
            'judul' => 'Esai Dinilai',
            'isi' => 'Konten esai',
            'tautan' => null,
            'dibaca_pada' => null,
        ]);

        $this->actingAs($this->siswa)
            ->post(route('siswa.notifikasi.baca-semua'))
            ->assertRedirect(route('siswa.notifikasi.index'));

        $this->assertEquals(0, $this->siswa->notifikasi()->whereNull('dibaca_pada')->count());
    }

    public function test_penanda_jumlah_belum_dibaca_benar_di_header_siswa(): void
    {
        Notifikasi::create([
            'user_id' => $this->siswa->id,
            'jenis' => 'langkah_terbuka',
            'referensi' => 'bab_1',
            'judul' => 'Judul 1',
            'isi' => 'Isi 1',
            'dibaca_pada' => null,
        ]);

        Notifikasi::create([
            'user_id' => $this->siswa->id,
            'jenis' => 'esai_dinilai',
            'referensi' => 'percobaan_1',
            'judul' => 'Judul 2',
            'isi' => 'Isi 2',
            'dibaca_pada' => null,
        ]);

        $response = $this->actingAs($this->siswa)->get(route('siswa.beranda'));
        $response->assertOk();
        // Badge lonceng menampilkan angka 2
        $response->assertSee('2');
    }
}
