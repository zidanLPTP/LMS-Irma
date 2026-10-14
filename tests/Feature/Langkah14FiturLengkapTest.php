<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\Pengaturan;
use App\Models\PercobaanKuis;
use App\Models\ProgresMateri;
use App\Models\Soal;
use App\Models\User;
use App\Services\BukaAksesService;
use App\Services\ImporSiswaService;
use App\Services\ImporSoalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class Langkah14FiturLengkapTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Irma Pengelola',
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

    /**
     * Helper untuk membuat UploadedFile spreadsheet .xlsx sementara.
     */
    private function buatBerkasXlsx(array $rows, string $namaBerkas = 'uji.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, $namaBerkas, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    // ==========================================
    // 1. IMPOR SISWA DARI EXCEL
    // ==========================================

    public function test_templat_impor_siswa_dan_soal_dapat_diunduh_oleh_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.impor-siswa.templat'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=templat_impor_siswa.xlsx');

        $this->actingAs($this->admin)
            ->get(route('admin.impor-soal.templat'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=templat_impor_soal_kuis.xlsx');

        // Siswa dilarang unduh
        $this->actingAs($this->siswa)->get(route('admin.impor-siswa.templat'))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.impor-soal.templat'))->assertForbidden();
    }

    public function test_impor_siswa_menolak_berkas_bukan_xlsx_atau_melebihi_satu_mb(): void
    {
        $berkasCsv = UploadedFile::fake()->create('siswa.csv', 10, 'text/csv');
        $this->actingAs($this->admin)
            ->post(route('admin.impor-siswa.pratinjau'), ['berkas' => $berkasCsv])
            ->assertSessionHasErrors('berkas');

        $berkasBesar = UploadedFile::fake()->create('siswa_besar.xlsx', 1500, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($this->admin)
            ->post(route('admin.impor-siswa.pratinjau'), ['berkas' => $berkasBesar])
            ->assertSessionHasErrors('berkas');
    }

    public function test_impor_siswa_menolak_berkas_lebih_dari_100_baris(): void
    {
        $service = app(ImporSiswaService::class);

        $baris = [['nama_lengkap', 'username', 'email', 'kelas']];
        for ($i = 1; $i <= 105; $i++) {
            $baris[] = ["Siswa {$i}", "siswa_{$i}", "siswa{$i}@test.com", 'XI'];
        }

        $berkas = $this->buatBerkasXlsx($baris, '105_siswa.xlsx');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jumlah baris data melebihi batas maksimal 100 baris.');
        $service->pratinjau($berkas);
    }

    public function test_impor_siswa_pratinjau_tidak_menyimpan_apa_pun_dan_mendeteksi_duplikasi_serta_rumus_tidak_dievaluasi(): void
    {
        $service = app(ImporSiswaService::class);

        $baris = [
            ['nama_lengkap', 'username', 'email', 'kelas'],
            // 1. Baris valid normal
            ['Budi Raharjo', 'budi_raharjo', 'budi@siswa.test', 'XI MIPA 1'],
            // 2. Berisi rumus di kolom nama / kelas -> wajib ditolak dengan alasan 'rumus tidak diizinkan'
            ['=SUM(1,2)', 'citra_k', 'citra@siswa.test', 'XI'],
            // 3. Duplikat username di sistem (ahmad_siswa)
            ['Ahmad Duplikat', 'ahmad_siswa', 'ahmad2@siswa.test', 'XI'],
            // 4. Duplikat email di sistem (ahmad@siswa.test)
            ['Siswa Email Sama', 'siswa_email_sama', 'ahmad@siswa.test', 'XI'],
            // 5. Duplikat di dalam berkas itu sendiri
            ['Budi Kembar', 'budi_raharjo', 'budi_kembar@siswa.test', 'XI'],
            // 6. Data kosong / invalid
            ['', 'tanpa_nama', 'salah_email', 'XI'],
        ];

        $berkas = $this->buatBerkasXlsx($baris, 'siswa_campur.xlsx');
        $hasil = $service->pratinjau($berkas);

        // Belum ada user baru tersimpan
        $this->assertDatabaseMissing('users', ['username' => 'budi_raharjo']);
        $this->assertCount(1, $hasil['baris_valid']);
        $this->assertCount(5, $hasil['baris_galat']);

        // Verifikasi baris rumus ditolak dengan alasan jelas
        $alasanGalatRumus = array_column($hasil['baris_galat'], 'alasan');
        $ditemukanPesanRumus = false;
        foreach ($alasanGalatRumus as $alasan) {
            if (str_contains($alasan, 'rumus tidak diizinkan')) {
                $ditemukanPesanRumus = true;
                break;
            }
        }
        $this->assertTrue($ditemukanPesanRumus, "Harus terdapat pesan 'rumus tidak diizinkan' pada baris berrumus.");
    }

    public function test_konfirmasi_impor_siswa_atomik_dan_menulis_riwayat_tindakan_admin(): void
    {
        $service = app(ImporSiswaService::class);

        $barisValid = [
            [
                'nomor_baris' => 2,
                'nama_lengkap' => 'Doni Salman',
                'username' => 'doni_salman',
                'email' => 'doni@siswa.test',
                'kelas' => 'XI MIPA 2',
            ],
            [
                'nomor_baris' => 3,
                'nama_lengkap' => 'Eka Putri',
                'username' => 'eka_putri',
                'email' => 'eka@siswa.test',
                'kelas' => 'XI MIPA 2',
            ],
        ];

        $hasilAkun = $service->konfirmasi($barisValid, $this->admin->id, 'siswa_baru.xlsx', 1);

        $this->assertCount(2, $hasilAkun);
        $this->assertDatabaseHas('users', ['username' => 'doni_salman', 'wajib_ganti_password' => true]);
        $this->assertDatabaseHas('users', ['username' => 'eka_putri', 'wajib_ganti_password' => true]);

        // Cek password hash
        $doni = User::where('username', 'doni_salman')->first();
        $this->assertTrue(Hash::check($hasilAkun[0]['kata_sandi_sementara'], $doni->password));

        // Cek riwayat tindakan admin
        $this->assertDatabaseHas('riwayat_tindakan_admin', [
            'admin_id' => $this->admin->id,
            'jenis' => 'impor_siswa',
        ]);
    }

    public function test_unduhan_daftar_akun_sesi_hanya_tampil_sekali_dan_dilindungi_aman_value_binder(): void
    {
        // Masukkan data akun sementara ke session
        $dataAkun = [
            [
                'nama_lengkap' => '=CMD() Injeksi',
                'username' => 'siswa_injeksi',
                'email' => 'injeksi@siswa.test',
                'kelas' => 'XI',
                'kata_sandi_sementara' => 'Pass123456',
            ],
        ];

        session()->flash('hasil_impor_siswa', $dataAkun);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.impor-siswa.unduh-akun'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=daftar_akun_siswa_sementara.xlsx');

        // Pengunduhan kedua harus ditolak karena sesi sudah dihapus (sekali pakai)
        $this->actingAs($this->admin)
            ->get(route('admin.impor-siswa.unduh-akun'))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('galat');
    }

    // ==========================================
    // 2. IMPOR SOAL DARI EXCEL
    // ==========================================

    public function test_impor_soal_memvalidasi_persis_soal_request_dan_atomik(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Bab 1',
            'tipe_kuis' => 'kuis_bab',
            'acak_soal' => false,
        ]);

        $service = app(ImporSoalService::class);

        // 1. Berkas berisi baris benar dan baris salah
        $baris = [
            ['tipe', 'pertanyaan', 'bobot', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'kunci', 'kunci_isian_1', 'kunci_isian_2', 'kunci_isian_3'],
            // Valid PG
            ['pilihan_ganda', 'Organel energi?', 10, 'Mitokondria', 'Kloroplas', '', '', '', 'A', '', '', ''],
            // Salah: PG tanpa kunci yang cocok
            ['pilihan_ganda', 'Organel protein?', 10, 'Ribosom', 'Lisosom', '', '', '', 'C', '', '', ''],
            // Salah: Isian tanpa kunci
            ['isian', 'Tempat DNA?', 10, '', '', '', '', '', '', '', '', ''],
            // Salah: Soal mengandung rumus di pertanyaan atau pilihan -> wajib ditolak 'rumus tidak diizinkan'
            ['pilihan_ganda', '=SUM(1,2)', 10, 'A', 'B', '', '', '', 'A', '', '', ''],
            // Valid Esai
            ['esai', 'Jelaskan respirasi?', 20, '', '', '', '', '', '', '', '', ''],
        ];

        $berkas = $this->buatBerkasXlsx($baris, 'soal_campur.xlsx');
        $hasil = $service->pratinjau($berkas, $kuis);

        $this->assertCount(2, $hasil['baris_valid']);
        $this->assertCount(3, $hasil['baris_galat']);

        // Verifikasi terdapat pesan 'rumus tidak diizinkan' pada baris berrumus
        $alasanGalat = array_column($hasil['baris_galat'], 'alasan');
        $adaPesanRumus = false;
        foreach ($alasanGalat as $alasan) {
            if (str_contains($alasan, 'rumus tidak diizinkan')) {
                $adaPesanRumus = true;
                break;
            }
        }
        $this->assertTrue($adaPesanRumus, "Harus terdapat alasan 'rumus tidak diizinkan' pada impor soal.");

        // Konfirmasi gagal (atomik) bila baris galat tidak kosong
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tidak dapat mengimpor: terdapat baris yang galat.');
        $service->konfirmasi($kuis, $hasil['baris_valid'], $hasil['baris_galat']);
    }

    public function test_impor_soal_berhasil_disimpan_secara_berurutan_di_akhir_kuis(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Bab 1',
            'tipe_kuis' => 'kuis_bab',
            'acak_soal' => false,
        ]);

        $service = app(ImporSoalService::class);

        $barisValid = [
            [
                'nomor_baris' => 2,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => 'Apa itu sel?',
                'bobot' => 10,
                'pilihan' => ['A' => 'Unit dasar hidup', 'B' => 'Molekul air'],
                'kunci_pg' => 'A',
                'kunci_isian' => [],
            ],
            [
                'nomor_baris' => 3,
                'tipe' => 'isian',
                'pertanyaan' => 'Sebutkan organel fotosintesis!',
                'bobot' => 15,
                'pilihan' => [],
                'kunci_pg' => null,
                'kunci_isian' => ['kloroplas'],
            ],
        ];

        $jumlah = $service->konfirmasi($kuis, $barisValid, []);
        $this->assertSame(2, $jumlah);
        $this->assertEquals(2, $kuis->soal()->count());
        $this->assertDatabaseHas('soal', ['kuis_id' => $kuis->id, 'urutan' => 1, 'pertanyaan' => 'Apa itu sel?']);
        $this->assertDatabaseHas('soal', ['kuis_id' => $kuis->id, 'urutan' => 2, 'pertanyaan' => 'Sebutkan organel fotosintesis!']);
    }

    // ==========================================
    // 3. PRATINJAU SEBAGAI SISWA OLEH ADMIN
    // ==========================================

    public function test_rute_pratinjau_admin_menolak_siswa_dan_tidak_mencatat_log_atau_progres(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $materi = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Materi Video Sel',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube-nocookie.com/embed/demo',
            'urutan' => 1,
        ]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Evaluasi',
            'tipe_kuis' => 'kuis_bab',
            'acak_soal' => false,
        ]);

        // Siswa dilarang membuka rute pratinjau admin (403)
        $this->actingAs($this->siswa)->get(route('admin.pratinjau.bab', $bab))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.pratinjau.materi', [$bab, $materi]))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.pratinjau.kuis', $kuis))->assertForbidden();

        // Admin berhasil buka pratinjau bab
        $resBab = $this->actingAs($this->admin)->get(route('admin.pratinjau.bab', $bab));
        $resBab->assertOk();
        $resBab->assertSee('Mode Pratinjau Guru/Admin');

        // Admin berhasil buka pratinjau materi
        $resMateri = $this->actingAs($this->admin)->get(route('admin.pratinjau.materi', [$bab, $materi]));
        $resMateri->assertOk();
        $resMateri->assertSee('Mode Pratinjau Guru/Admin');

        // Pastikan TIDAK ADA baris progres_materi yang dibuat saat admin pratinjau
        $this->assertEquals(0, ProgresMateri::count());

        // Admin buka pratinjau kuis
        $resKuis = $this->actingAs($this->admin)->get(route('admin.pratinjau.kuis', $kuis));
        $resKuis->assertOk();
        $resKuis->assertSee('Mode Pratinjau Kuis');
        $this->assertEquals(0, PercobaanKuis::count());
    }

    // ==========================================
    // 4. PENCARIAN SISWA
    // ==========================================

    public function test_pencarian_siswa_menyembunyikan_bab_terkunci_dan_draf_serta_menolak_kurang_dari_dua_karakter(): void
    {
        // Bab 1 terbit (terbuka)
        $bab1 = Bab::create(['judul' => 'Bab Sel Hewan Terbuka', 'urutan' => 1, 'status_terbit' => true]);
        $materi1 = Materi::create(['bab_id' => $bab1->id, 'judul' => 'Struktur Sel Hewan', 'tipe' => 'youtube', 'url_youtube' => 'https://youtube-nocookie.com/embed/1', 'urutan' => 1]);

        // Bab 2 terbit tapi terkunci bagi siswa (karena kuis bab 1 belum lulus)
        $kuisBab1 = Kuis::create(['bab_id' => $bab1->id, 'tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Bab 1', 'acak_soal' => false]);
        $bab2 = Bab::create(['judul' => 'Bab Jaringan Tumbuhan Terkunci', 'urutan' => 2, 'status_terbit' => true]);
        $materi2 = Materi::create(['bab_id' => $bab2->id, 'judul' => 'Struktur Sel Tumbuhan Rahasia', 'tipe' => 'youtube', 'url_youtube' => 'https://youtube-nocookie.com/embed/2', 'urutan' => 1]);

        // Bab 3 draf (status_terbit = false)
        $bab3 = Bab::create(['judul' => 'Bab Draf Belum Terbit', 'urutan' => 3, 'status_terbit' => false]);

        // 1. Kurang dari 2 karakter -> peringatan
        $this->actingAs($this->siswa)
            ->get(route('siswa.cari', ['q' => 'a']))
            ->assertOk()
            ->assertSee('Masukkan minimal 2 karakter');

        // 2. Cari 'sel': Hanya bab dan materi terbuka yang muncul, bab terkunci & draf tersembunyi
        $response = $this->actingAs($this->siswa)
            ->get(route('siswa.cari', ['q' => 'sel']));

        $response->assertOk();
        $response->assertSee('Bab Sel Hewan Terbuka');
        $response->assertSee('Struktur Sel Hewan');

        // Bab terkunci dan materinya TIDAK BOLEH muncul sama sekali
        $response->assertDontSee('Bab Jaringan Tumbuhan Terkunci');
        $response->assertDontSee('Struktur Sel Tumbuhan Rahasia');
        $response->assertDontSee('Bab Draf Belum Terbit');
    }

    // ==========================================
    // 5. TANDAI MATERI SELESAI
    // ==========================================

    public function test_tandai_materi_selesai_idempoten_bisa_dibatalkan_dan_tidak_mengubah_buka_akses(): void
    {
        $bab = Bab::create(['judul' => 'Bab 1 Biologi', 'urutan' => 1, 'status_terbit' => true]);
        $materi = Materi::create([
            'bab_id' => $bab->id,
            'judul' => 'Materi Membran Sel',
            'tipe' => 'youtube',
            'url_youtube' => 'https://youtube-nocookie.com/embed/abc',
            'urutan' => 1,
        ]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 1',
            'acak_soal' => false,
        ]);
        Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal penentu kelulusan?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $bukaAksesService = app(BukaAksesService::class);

        // 1. Tandai selesai pertama kali
        $this->actingAs($this->siswa)
            ->post(route('siswa.bab.materi.toggle-selesai', [$bab, $materi]))
            ->assertRedirect();

        $progres = ProgresMateri::where('user_id', $this->siswa->id)->where('materi_id', $materi->id)->first();
        $this->assertNotNull($progres);
        $this->assertNotNull($progres->selesai_pada);

        // Halaman bab menampilkan lencana Selesai dan 1 dari 1 materi selesai
        $resBab = $this->actingAs($this->siswa)->get(route('siswa.bab.show', $bab));
        $resBab->assertOk();
        $resBab->assertSee('1</strong> dari 1 materi selesai', false);
        $resBab->assertSee('Selesai');

        // Aturan BukaAksesService TIDAK terpengaruh: Bab selesai ditentukan kelulusan kuis, bukan penanda materi selesai
        $this->assertFalse($bukaAksesService->apakahBabSelesai($bab, $this->siswa));

        // 2. Klik lagi -> membatalkan tanda selesai
        $this->actingAs($this->siswa)
            ->post(route('siswa.bab.materi.toggle-selesai', [$bab, $materi]))
            ->assertRedirect();

        $progres->refresh();
        $this->assertNull($progres->selesai_pada);
    }

    // ==========================================
    // 6. KONTROL PENDAFTARAN SISWA
    // ==========================================

    public function test_kontrol_pendaftaran_tutup_dan_verifikasi_kode_pendaftaran(): void
    {
        // 1. Kasus pendaftaran ditutup oleh admin
        Pengaturan::setel('pendaftaran_siswa', 'tutup');

        $this->get(route('daftar'))
            ->assertOk()
            ->assertSee('Pendaftaran Siswa Ditutup');

        // Permintaan POST ditolak
        $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Ditolak',
            'username' => 'siswa_ditolak',
            'email' => 'ditolak@siswa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['username' => 'siswa_ditolak']);

        // 2. Kasus pendaftaran buka tapi membutuhkan kode pendaftaran khusus
        Pengaturan::setel('pendaftaran_siswa', 'buka');
        Pengaturan::setel('kode_pendaftaran', 'BIOLOGI2026');

        // Kode salah -> ditolak
        $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Kode Salah',
            'username' => 'siswa_salah_kode',
            'email' => 'salah@siswa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'kode_pendaftaran' => 'SALAHKODE',
        ])->assertSessionHasErrors('kode_pendaftaran');

        $this->assertDatabaseMissing('users', ['username' => 'siswa_salah_kode']);

        // Kode benar -> diterima
        $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Kode Benar',
            'username' => 'siswa_benar_kode',
            'email' => 'benar@siswa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'kode_pendaftaran' => 'BIOLOGI2026',
        ])->assertRedirect(route('siswa.beranda'));

        $this->assertDatabaseHas('users', ['username' => 'siswa_benar_kode']);
    }

    public function test_pengaturan_pendaftaran_tercatat_di_riwayat_pengaturan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.pengaturan.update'), [
                'nama_situs' => 'LMS Terkunci',
                'nilai_lulus' => 65,
                'batas_waktu_per_soal' => 60,
                'maksimal_kesempatan' => 2,
                'pendaftaran_siswa' => 'tutup',
                'kode_pendaftaran' => 'KODE_RAHASIA_123',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('riwayat_pengaturan', [
            'admin_id' => $this->admin->id,
            'kunci' => 'pendaftaran_siswa',
            'nilai_baru' => 'tutup',
        ]);

        $this->assertDatabaseHas('riwayat_pengaturan', [
            'admin_id' => $this->admin->id,
            'kunci' => 'kode_pendaftaran',
            'nilai_baru' => 'KODE_RAHASIA_123',
        ]);
    }

    public function test_otorisasi_ketat_rute_impor_dan_pratinjau_menolak_siswa_dan_tamu(): void
    {
        $bab = Bab::create(['judul' => 'Bab Uji Otorisasi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create(['bab_id' => $bab->id, 'judul' => 'Kuis Uji', 'tipe_kuis' => 'kuis_bab']);

        $ruteAdmin = [
            route('admin.impor-siswa.create'),
            route('admin.impor-siswa.templat'),
            route('admin.impor-soal.create', $kuis),
            route('admin.impor-soal.templat'),
            route('admin.impor-siswa.unduh-akun'),
            route('admin.pratinjau.bab', $bab),
            route('admin.pratinjau.kuis', $kuis),
        ];

        foreach ($ruteAdmin as $url) {
            // Tamu dialihkan ke login
            $resTamu = $this->app['auth']->guard('web')->logout();
            $this->flushSession();
            $this->get($url)->assertRedirect(route('masuk'));

            // Siswa ditolak 403 Forbidden
            $this->actingAs($this->siswa)->get($url)->assertForbidden();
        }
    }

    public function test_kata_sandi_sementara_hasil_impor_tidak_ada_di_basis_data_disk_dan_logs(): void
    {
        $service = app(ImporSiswaService::class);
        $barisValid = [
            [
                'nomor_baris' => 2,
                'nama_lengkap' => 'Siswa Audit Pass',
                'username' => 'siswa_audit_pass',
                'email' => 'audit_pass@siswa.test',
                'kelas' => 'XI MIPA',
            ],
        ];

        $hasilAkun = $service->konfirmasi($barisValid, $this->admin->id, 'siswa.xlsx');
        $this->assertNotEmpty($hasilAkun);
        $kataSandiSementara = $hasilAkun[0]['kata_sandi_sementara'];

        // 1. Periksa tabel users: kata sandi adalah hash bcrypt valid, teks asli TIDAK ADA di kolom password
        $user = User::where('username', 'siswa_audit_pass')->firstOrFail();
        $this->assertNotSame($kataSandiSementara, $user->password);
        $this->assertTrue(Hash::check($kataSandiSementara, $user->password));

        // 2. Periksa disk storage/logs: kata sandi sementara tidak tertulis di berkas log mana pun
        $logPath = storage_path('logs');
        if (is_dir($logPath)) {
            $berkasLogs = glob($logPath.'/*.log');
            foreach ($berkasLogs as $berkasLog) {
                $isiLog = file_get_contents($berkasLog);
                $this->assertStringNotContainsString(
                    $kataSandiSementara,
                    $isiLog,
                    "Kata sandi sementara tidak boleh bocor ke berkas log: {$berkasLog}"
                );
            }
        }
    }

    public function test_impor_soal_menolak_berkas_lebih_dari_satu_mb_atau_bukan_xlsx_atau_lebih_dari_100_soal(): void
    {
        $bab = Bab::create(['judul' => 'Bab Batas Soal', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create(['bab_id' => $bab->id, 'judul' => 'Kuis Batas', 'tipe_kuis' => 'kuis_bab']);

        // 1. Bukan XLSX
        $fileTxt = UploadedFile::fake()->create('soal.txt', 10, 'text/plain');
        $this->actingAs($this->admin)
            ->post(route('admin.impor-soal.pratinjau', $kuis), ['berkas' => $fileTxt])
            ->assertSessionHasErrors('berkas');

        // 2. Lebih dari 1 MB
        $fileBesar = UploadedFile::fake()->create('soal_besar.xlsx', 1500, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($this->admin)
            ->post(route('admin.impor-soal.pratinjau', $kuis), ['berkas' => $fileBesar])
            ->assertSessionHasErrors('berkas');

        // 3. Lebih dari 100 soal
        $service = app(ImporSoalService::class);
        $baris = [['tipe', 'pertanyaan', 'bobot', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'kunci', 'kunci_isian_1', 'kunci_isian_2', 'kunci_isian_3']];
        for ($i = 1; $i <= 105; $i++) {
            $baris[] = ['esai', "Soal {$i}", 10, '', '', '', '', '', '', '', '', ''];
        }

        $berkas105 = $this->buatBerkasXlsx($baris, '105_soal.xlsx');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jumlah baris soal melebihi batas maksimal 100 baris.');
        $service->pratinjau($berkas105, $kuis);
    }
}
