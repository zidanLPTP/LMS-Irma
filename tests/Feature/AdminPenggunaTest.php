<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\PercobaanKuis;
use App\Models\RiwayatTindakanAdmin;
use App\Models\Soal;
use App\Models\User;
use App\Services\BukaAksesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siswa;

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

    /**
     * 1. Tamu diarahkan ke login dan siswa mendapat 403 di semua rute kelola pengguna.
     */
    public function test_siswa_dilarang_akses_dan_tamu_diarahkan_ke_masuk_pada_semua_rute_pengguna(): void
    {
        // Index
        auth()->logout();
        $this->get(route('admin.pengguna.index'))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->get(route('admin.pengguna.index'))->assertForbidden();

        // Create & Store
        auth()->logout();
        $this->get(route('admin.pengguna.create'))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->get(route('admin.pengguna.create'))->assertForbidden();
        auth()->logout();
        $this->post(route('admin.pengguna.store'), ['nama_lengkap' => 'X'])->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->post(route('admin.pengguna.store'), ['nama_lengkap' => 'X'])->assertForbidden();

        // Show
        auth()->logout();
        $this->get(route('admin.pengguna.show', $this->siswa))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->get(route('admin.pengguna.show', $this->siswa))->assertForbidden();

        // Edit & Update
        auth()->logout();
        $this->get(route('admin.pengguna.edit', $this->siswa))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->get(route('admin.pengguna.edit', $this->siswa))->assertForbidden();
        auth()->logout();
        $this->put(route('admin.pengguna.update', $this->siswa), ['nama_lengkap' => 'X'])->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->put(route('admin.pengguna.update', $this->siswa), ['nama_lengkap' => 'X'])->assertForbidden();

        // Toggle Aktif
        auth()->logout();
        $this->patch(route('admin.pengguna.toggle-aktif', $this->siswa))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->patch(route('admin.pengguna.toggle-aktif', $this->siswa))->assertForbidden();

        // Reset Password
        auth()->logout();
        $this->post(route('admin.pengguna.reset-password', $this->siswa))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->post(route('admin.pengguna.reset-password', $this->siswa))->assertForbidden();

        // Reset Kesempatan
        auth()->logout();
        $this->post(route('admin.pengguna.reset-kesempatan', $this->siswa), ['kuis_id' => 1, 'alasan' => 'tes'])->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->post(route('admin.pengguna.reset-kesempatan', $this->siswa), ['kuis_id' => 1, 'alasan' => 'tes'])->assertForbidden();

        // Akun Ganda
        auth()->logout();
        $this->get(route('admin.pengguna.ganda'))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->get(route('admin.pengguna.ganda'))->assertForbidden();

        // Hapus
        auth()->logout();
        $this->delete(route('admin.pengguna.destroy', $this->siswa))->assertRedirect(route('masuk'));
        $this->actingAs($this->siswa)->delete(route('admin.pengguna.destroy', $this->siswa))->assertForbidden();
    }

    /**
     * 2. Daftar siswa mendukung cari dan filter serta menampilkan kolom dengan agregasi bebas N+1.
     */
    public function test_daftar_siswa_mendukung_cari_dan_filter_kelas_status(): void
    {
        $siswa2 = User::factory()->create([
            'nama_lengkap' => 'Budi Pratama',
            'username' => 'budi_pratama',
            'email' => 'budi@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 2',
            'aktif' => false,
        ]);

        LogAkses::create([
            'user_id' => $this->siswa->id,
            'waktu_masuk' => now()->subHours(2),
            'aktivitas_terakhir' => now()->subHours(1),
            'durasi_detik' => 3600,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pengguna.index'));
        $response->assertOk();
        $response->assertSee('Ahmad Fauzi');
        $response->assertSee('Budi Pratama');
        $response->assertSee('XI MIPA 1');
        $response->assertSee('Aktif');
        $response->assertSee('Nonaktif');

        // Filter cari
        $resCari = $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['cari' => 'budi']));
        $resCari->assertOk();
        $resCari->assertSee('Budi Pratama');
        $resCari->assertDontSee('Ahmad Fauzi');

        // Filter status
        $resStatus = $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['status' => 'nonaktif']));
        $resStatus->assertOk();
        $resStatus->assertSee('Budi Pratama');
        $resStatus->assertDontSee('Ahmad Fauzi');

        // Filter kelas
        $resKelas = $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['kelas' => 'XI MIPA 1']));
        $resKelas->assertOk();
        $resKelas->assertSee('Ahmad Fauzi');
        $resKelas->assertDontSee('Budi Pratama');
    }

    /**
     * 3a. Tambah siswa manual: password awal dibuat kata sandi sementara acak 10 karakter, tampil SATU KALI di session, wajib_ganti_password = true, password tersimpan sebagai hash, tidak bocor di log.
     */
    public function test_tambah_siswa_manual_menghasilkan_sandi_sementara_acak_dan_wajib_ganti_password(): void
    {
        $resStore = $this->actingAs($this->admin)->post(route('admin.pengguna.store'), [
            'nama_lengkap' => 'Citra Lestari',
            'username' => 'citra_lestari',
            'email' => 'citra@siswa.test',
            'kelas' => 'XI MIPA 3',
            'peran' => 'admin', // Coba manipulasi peran
            'aktif' => false,   // Coba manipulasi status
        ]);

        $resStore->assertRedirect(route('admin.pengguna.index'));
        $resStore->assertSessionHas('sandi_sementara_baru');

        $dataSandi = session('sandi_sementara_baru');
        $this->assertIsArray($dataSandi);
        $this->assertEquals('Citra Lestari', $dataSandi['nama']);
        $this->assertEquals('citra_lestari', $dataSandi['username']);
        $sandiSementara = $dataSandi['sandi'];
        $this->assertIsString($sandiSementara);
        $this->assertSame(10, strlen($sandiSementara));

        $siswaBaru = User::where('username', 'citra_lestari')->first();
        $this->assertNotNull($siswaBaru);
        $this->assertSame('siswa', $siswaBaru->peran); // Tetap siswa
        $this->assertTrue($siswaBaru->aktif);          // Tetap aktif
        $this->assertTrue($siswaBaru->wajib_ganti_password); // Siswa baru wajib ganti password

        // Password tersimpan sebagai hash valid dan bukan teks mentah
        $this->assertNotSame($sandiSementara, $siswaBaru->password);
        $this->assertTrue(Hash::check($sandiSementara, $siswaBaru->password));

        // Teks kata sandi sementara TIDAK ada di berkas log
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $isiLog = file_get_contents($logPath);
            $this->assertStringNotContainsString($sandiSementara, $isiLog);
        }

        // Cek jejak audit tambah siswa (jenis ubah_data) dengan keterangan benar
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $siswaBaru->id,
            'jenis' => 'ubah_data',
            'keterangan' => 'Menambahkan akun siswa baru secara manual.',
        ]);

        // Coba tambah dengan username kembar -> gagal
        $resGagal = $this->actingAs($this->admin)->post(route('admin.pengguna.store'), [
            'nama_lengkap' => 'Citra Lain',
            'username' => 'citra_lestari',
            'email' => 'citra_lain@siswa.test',
            'kelas' => 'XI MIPA 3',
        ]);
        $resGagal->assertSessionHasErrors('username');
    }

    /**
     * 3b. Ubah data siswa menulis jejak audit ubah_data dengan keterangan identitas yang benar.
     */
    public function test_ubah_data_siswa_menulis_jejak_audit(): void
    {
        $siswaUji = User::factory()->create([
            'nama_lengkap' => 'Eka Pratama',
            'username' => 'eka_pratama',
            'email' => 'eka@siswa.test',
            'kelas' => 'XI MIPA 1',
            'peran' => 'siswa',
        ]);

        $resUpdate = $this->actingAs($this->admin)->put(route('admin.pengguna.update', $siswaUji), [
            'nama_lengkap' => 'Eka Pratama Putra',
            'username' => 'eka_pratama',
            'email' => 'eka_putra@siswa.test',
            'kelas' => 'XI MIPA 2',
            'peran' => 'admin', // Diabaikan
        ]);

        $resUpdate->assertRedirect(route('admin.pengguna.show', $siswaUji));
        $siswaUji->refresh();
        $this->assertSame('Eka Pratama Putra', $siswaUji->nama_lengkap);
        $this->assertSame('eka_putra@siswa.test', $siswaUji->email);
        $this->assertSame('XI MIPA 2', $siswaUji->kelas);
        $this->assertSame('siswa', $siswaUji->peran);

        // Jejak audit ubah_data tertulis
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $siswaUji->id,
            'jenis' => 'ubah_data',
            'keterangan' => 'Memperbarui data identitas profil siswa.',
        ]);
    }

    /**
     * 4. Nonaktifkan dan aktifkan akun: admin tidak bisa nonaktifkan dirinya, siswa nonaktif ditendang seketika dari sesi.
     */
    public function test_nonaktifkan_dan_aktifkan_akun_serta_tendang_sesi_siswa(): void
    {
        // Admin tidak bisa menonaktifkan dirinya sendiri
        $resSelf = $this->actingAs($this->admin)->patch(route('admin.pengguna.toggle-aktif', $this->admin));
        $resSelf->assertForbidden();
        $this->assertTrue($this->admin->fresh()->aktif);

        // Admin menonaktifkan siswa
        $resToggle = $this->actingAs($this->admin)->patch(route('admin.pengguna.toggle-aktif', $this->siswa));
        $resToggle->assertRedirect();
        $this->assertFalse($this->siswa->fresh()->aktif);

        // Jejak audit nonaktifkan
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'jenis' => 'nonaktifkan',
        ]);

        // Siswa yang sedang login mencoba mengakses beranda -> langsung ditendang dan sesi dibatalkan
        $this->siswa->refresh();
        $resSiswa = $this->actingAs($this->siswa)->get(route('siswa.beranda'));
        $resSiswa->assertRedirect(route('masuk'));
        $resSiswa->assertSessionHas('galat');
        $this->assertGuest();

        // Siswa nonaktif mencoba login -> ditolak
        $resLogin = $this->post(route('masuk.kirim'), [
            'login' => 'ahmad_fauzi',
            'password' => 'password123',
        ]);
        $resLogin->assertSessionHasErrors('login');

        // Admin mengaktifkan kembali
        $this->actingAs($this->admin)->patch(route('admin.pengguna.toggle-aktif', $this->siswa));
        $this->assertTrue($this->siswa->fresh()->aktif);
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'jenis' => 'aktifkan',
        ]);
    }

    /**
     * 5. Reset password: admin reset password sementara acak 10 karakter, tampil sekali di layar, wajib_ganti_password=true, tidak disimpan teks biasa.
     */
    public function test_reset_password_oleh_admin_menampilkan_sandi_sekali_dan_memaksa_ganti(): void
    {
        // Admin tidak bisa reset dirinya sendiri
        $resSelf = $this->actingAs($this->admin)->post(route('admin.pengguna.reset-password', $this->admin));
        $resSelf->assertForbidden();

        // Admin mereset password siswa
        $resReset = $this->actingAs($this->admin)->post(route('admin.pengguna.reset-password', $this->siswa));
        $resReset->assertRedirect(route('admin.pengguna.show', $this->siswa));
        $resReset->assertSessionHas('sandi_sementara');

        $sandiSementara = session('sandi_sementara');
        $this->assertIsString($sandiSementara);
        $this->assertSame(10, strlen($sandiSementara));

        $this->siswa->refresh();
        $this->assertTrue($this->siswa->wajib_ganti_password);
        // Pastikan kolom password adalah hash (bukan teks biasa)
        $this->assertNotSame($sandiSementara, $this->siswa->password);
        $this->assertTrue(Hash::check($sandiSementara, $this->siswa->password));

        // Pastikan kata sandi sementara TIDAK bocor di berkas log
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $isiLog = file_get_contents($logPath);
            $this->assertStringNotContainsString($sandiSementara, $isiLog);
        }

        // Jejak audit reset password
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'jenis' => 'reset_password',
        ]);

        // Siswa login menggunakan password sementara -> masuk berhasil
        auth()->logout();
        $resMasuk = $this->post(route('masuk.kirim'), [
            'login' => 'ahmad_fauzi',
            'password' => $sandiSementara,
        ]);
        $resMasuk->assertRedirect(route('siswa.beranda'));

        // Akses rute beranda siswa saat wajib_ganti_password = true -> dipaksa ke form ganti password
        $resBlokir = $this->actingAs($this->siswa)->get(route('siswa.beranda'));
        $resBlokir->assertRedirect(route('siswa.password.edit'));
    }

    /**
     * 6. Halaman ganti password siswa: butuh password lama yang benar, minimal 8, konfirmasi, dan menghapus wajib_ganti_password.
     */
    public function test_siswa_ganti_password_berhasil_dan_menghapus_status_wajib(): void
    {
        $this->siswa->update([
            'password' => Hash::make('SandiLama123!'),
            'wajib_ganti_password' => true,
        ]);

        // Form ganti password bisa dibuka
        $resPage = $this->actingAs($this->siswa)->get(route('siswa.password.edit'));
        $resPage->assertOk();
        $resPage->assertSee('Ganti Kata Sandi');

        // Password lama salah -> gagal
        $resSalah = $this->actingAs($this->siswa)->put(route('siswa.password.update'), [
            'password_lama' => 'SalahBanget',
            'password' => 'SandiBaruBaru123',
            'password_confirmation' => 'SandiBaruBaru123',
        ]);
        $resSalah->assertSessionHasErrors('password_lama');

        // Password baru berhasil
        $resSukses = $this->actingAs($this->siswa)->put(route('siswa.password.update'), [
            'password_lama' => 'SandiLama123!',
            'password' => 'SandiBaruBaru123',
            'password_confirmation' => 'SandiBaruBaru123',
        ]);
        $resSukses->assertRedirect(route('siswa.beranda'));
        $resSukses->assertSessionHas('sukses');

        $this->siswa->refresh();
        $this->assertFalse($this->siswa->wajib_ganti_password);
        $this->assertTrue(Hash::check('SandiBaruBaru123', $this->siswa->password));

        // Sekarang beranda bisa dibuka tanpa redirect
        $this->actingAs($this->siswa)->get(route('siswa.beranda'))->assertOk();
    }

    /**
     * 7. Reset kesempatan kuis: alasan wajib, tandai dibatalkan tanpa menghapus, hitung ulang akses bab, dan mulai lagi tanpa bentrok.
     */
    public function test_reset_kesempatan_kuis_oleh_admin_dan_dampaknya_pada_akses_bab(): void
    {
        $bab1 = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $bab2 = Bab::create(['judul' => 'Bab 2 Genetika', 'urutan' => 2, 'status_terbit' => true]);

        $kuisBab1 = Kuis::create([
            'bab_id' => $bab1->id,
            'judul' => 'Kuis Bab 1',
            'tipe_kuis' => 'kuis_bab',
            'nilai_lulus' => 65.00,
            'maksimal_kesempatan' => 2,
            'acak_soal' => false,
        ]);

        Soal::create([
            'kuis_id' => $kuisBab1->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Pertanyaan uji?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        // Siswa mengerjakan Kuis Bab 1 dan lulus
        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => 80.00,
            'nilai_akhir' => 80.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->subMinutes(40),
        ]);

        $bukaService = app(BukaAksesService::class);
        // Sebelum reset: Bab 2 terbuka karena Bab 1 sudah lulus
        $this->assertTrue($bukaService->apakahBabTerbuka($bab2, $this->siswa));

        // Admin reset kesempatan tanpa alasan -> gagal validasi
        $resTanpaAlasan = $this->actingAs($this->admin)->post(route('admin.pengguna.reset-kesempatan', $this->siswa), [
            'kuis_id' => $kuisBab1->id,
            'alasan' => '',
        ]);
        $resTanpaAlasan->assertSessionHasErrors('alasan');

        // Admin reset kesempatan dengan alasan valid
        $resReset = $this->actingAs($this->admin)->post(route('admin.pengguna.reset-kesempatan', $this->siswa), [
            'kuis_id' => $kuisBab1->id,
            'alasan' => 'Siswa mengalami kendala koneksi internet sekolah.',
        ]);
        $resReset->assertRedirect(route('admin.pengguna.show', $this->siswa));
        $resReset->assertSessionHas('sukses');

        // Percobaan tidak dihapus, tetapi dibatalkan
        $percobaan->refresh();
        $this->assertTrue($percobaan->dibatalkan);
        $this->assertEquals($this->admin->id, $percobaan->dibatalkan_oleh);
        $this->assertNotNull($percobaan->dibatalkan_pada);
        $this->assertSame('Siswa mengalami kendala koneksi internet sekolah.', $percobaan->alasan_pembatalan);

        // Jejak audit reset kesempatan
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'jenis' => 'reset_kesempatan',
        ]);

        // DAMPAK KE AKSES: Karena percobaan dibatalkan, Kuis Bab 1 belum lulus, sehingga Bab 2 kembali TERKUNCI!
        $this->assertFalse($bukaService->apakahBabTerbuka($bab2, $this->siswa));

        // Siswa membuat percobaan baru -> nomor percobaan harus 2 (tidak melanggar indeks unik)
        $nomorBaru = PercobaanKuis::nomorBerikutnya($kuisBab1->id, $this->siswa->id);
        $this->assertSame(2, $nomorBaru);

        $percobaanBaru = PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => $nomorBaru,
            'status_pengerjaan' => 'sedang_mengerjakan',
            'esai_menunggu_nilai' => false,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
        ]);
        $this->assertNotNull($percobaanBaru->id);
    }

    /**
     * 8. Akun ganda dan aturan hapus: akun berdata tidak bisa dihapus, akun tanpa data bisa dihapus.
     */
    public function test_halaman_akun_ganda_dan_proteksi_hapus_akun_berdata(): void
    {
        // Buat akun dengan nama kembar
        $siswaKembar1 = User::factory()->create([
            'nama_lengkap' => 'Dewi Sartika',
            'username' => 'dewi_1',
            'email' => 'dewi1@siswa.test',
            'peran' => 'siswa',
        ]);

        $siswaKembar2 = User::factory()->create([
            'nama_lengkap' => 'dewi sartika', // Huruf kecil
            'username' => 'dewi_2',
            'email' => 'dewi2@siswa.test',
            'peran' => 'siswa',
        ]);

        // Berikan data ke $siswaKembar1
        LogAkses::create([
            'user_id' => $siswaKembar1->id,
            'waktu_masuk' => now(),
            'aktivitas_terakhir' => now(),
            'durasi_detik' => 120,
        ]);

        // Buka halaman akun ganda
        $resGanda = $this->actingAs($this->admin)->get(route('admin.pengguna.ganda'));
        $resGanda->assertOk();
        $resGanda->assertSee('Dewi Sartika');
        $resGanda->assertSee('dewi_1');
        $resGanda->assertSee('dewi_2');

        // Coba hapus akun yang punya data ($siswaKembar1) -> ditolak
        $resHapusBerdata = $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $siswaKembar1));
        $resHapusBerdata->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $siswaKembar1->id]);

        // Coba hapus akun tanpa data ($siswaKembar2) -> berhasil
        $idSiswaKembar2 = $siswaKembar2->id;
        $resHapusKosong = $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $siswaKembar2));
        $resHapusKosong->assertRedirect(route('admin.pengguna.ganda'));
        $this->assertDatabaseMissing('users', ['id' => $idSiswaKembar2]);

        // Jejak audit hapus tetap ada, siswa_id menjadi null, dan keterangan menyimpan identitas siswa
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'siswa_id' => null,
            'jenis' => 'hapus',
            'keterangan' => 'Menghapus akun kosong siswa: dewi sartika (dewi_2).',
        ]);

        // Admin tidak bisa menghapus dirinya sendiri
        $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $this->admin))->assertForbidden();
    }

    /**
     * 9. Detail siswa menampilkan data akun, stepper progres BukaAksesService, riwayat kuis, 10 log, dan jejak audit.
     */
    public function test_detail_siswa_menampilkan_data_lengkap_dan_jejak_audit(): void
    {
        LogAkses::create([
            'user_id' => $this->siswa->id,
            'waktu_masuk' => now()->subMinutes(15),
            'aktivitas_terakhir' => now(),
            'durasi_detik' => 900,
        ]);

        RiwayatTindakanAdmin::create([
            'admin_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'jenis' => 'ubah_data',
            'keterangan' => 'Memperbaiki kelas siswa menjadi XI MIPA 1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.pengguna.show', $this->siswa));
        $response->assertOk();
        $response->assertSee('Ahmad Fauzi');
        $response->assertSee('XI MIPA 1');
        $response->assertSee('Tahapan Pembelajaran');
        $response->assertSee('10 Sesi Akses Terakhir');
        $response->assertSee('Jejak Tindakan Administrator');
        $response->assertSee('Memperbaiki kelas siswa menjadi XI MIPA 1');
    }
}
