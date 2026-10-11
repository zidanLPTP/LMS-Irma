<?php

namespace Tests\Feature;

use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\Soal;
use App\Models\User;
use App\Services\PenilaianKuisService;
use Database\Seeders\PengaturanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPenilaianEsaiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siswa;

    protected PenilaianKuisService $penilaianService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PengaturanSeeder::class);

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Irma Guru Biologi',
            'username' => 'irma_admin',
            'email' => 'irma@sekolah.test',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'email' => 'budi@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 2',
            'aktif' => true,
        ]);

        $this->penilaianService = app(PenilaianKuisService::class);
    }

    /**
     * 1. Antrean hanya memuat percobaan yang selesai, tidak dibatalkan, dan esai_menunggu_nilai = true.
     */
    public function test_antrean_hanya_memuat_percobaan_selesai_tidak_dibatalkan_dan_esai_menunggu(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Bab 1']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Jelaskan peran ribosom!',
            'bobot_poin' => 20,
            'urutan' => 1,
        ]);

        // Percobaan 1: Memenuhi syarat (selesai, tidak dibatalkan, esai menunggu)
        $p1 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now()->subMinutes(50),
        ]);
        $p1->jawabanSiswa()->create([
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => 'Ribosom untuk sintesis protein.',
            'waktu_mulai_soal' => now()->subHour(),
            'waktu_jawab' => now()->subMinutes(50),
        ]);

        // Percobaan 2: Sedang mengerjakan -> tidak boleh masuk antrean
        $p2 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'sedang_mengerjakan',
            'esai_menunggu_nilai' => true,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
        ]);

        // Percobaan 3: Dibatalkan -> tidak boleh masuk antrean
        $p3 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 3,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'dibatalkan' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        // Percobaan 4: Sudah dinilai (esai_menunggu_nilai = false) -> tidak masuk antrean
        $p4 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 4,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => false,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.penilaian-esai.index'));
        $res->assertOk();
        $res->assertSee('Budi Santoso');
        $res->assertSee('XI MIPA 2');
        $res->assertSee('Kuis Bab 1');
        $res->assertSee(route('admin.penilaian-esai.show', $p1));
        $res->assertDontSee(route('admin.penilaian-esai.show', $p2));
        $res->assertDontSee(route('admin.penilaian-esai.show', $p3));
        $res->assertDontSee(route('admin.penilaian-esai.show', $p4));
    }

    /**
     * 2. Menilai sebagian esai tidak mengisi nilai_akhir; menilai semua esai mengisi nilai_akhir,
     * flag esai_menunggu_nilai menjadi false, dan status_kelulusan tidak berubah.
     */
    public function test_menilai_sebagian_vs_semua_esai_dan_status_kelulusan_tetap(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis 2 Esai',
            'nilai_lulus' => 65.00,
            'maksimal_kesempatan' => 2,
        ]);
        $soalPg = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'PG 1',
            'bobot_poin' => 60,
            'urutan' => 1,
        ]);
        $soalEsai1 = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai 1',
            'bobot_poin' => 20,
            'urutan' => 2,
        ]);
        $soalEsai2 = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai 2',
            'bobot_poin' => 20,
            'urutan' => 3,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus', // Lulus karena PG dapat 100% dari 60
            'esai_menunggu_nilai' => true,
            'nilai_otomatis' => 100.00,
            'nilai_akhir' => null,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $jPg = $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalPg->id,
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
            'poin_diperoleh' => 60.00,
        ]);
        $jEsai1 = $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai1->id,
            'jawaban_teks' => 'Penjelasan Esai 1',
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
        ]);
        $jEsai2 = $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai2->id,
            'jawaban_teks' => 'Penjelasan Esai 2',
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
        ]);

        // Simpan nilai hanya untuk Esai 1 (sebagian)
        $resSebagian = $this->actingAs($this->admin)->post(route('admin.penilaian-esai.simpan', $percobaan), [
            'penilaian' => [
                $jEsai1->id => [
                    'poin' => 15,
                    'catatan' => 'Cukup bagus',
                ],
            ],
        ]);
        $resSebagian->assertRedirect(route('admin.penilaian-esai.show', $percobaan));

        $percobaan->refresh();
        $this->assertTrue((bool) $percobaan->esai_menunggu_nilai);
        $this->assertNull($percobaan->nilai_akhir);
        $this->assertEquals('lulus', $percobaan->status_kelulusan);

        // Simpan nilai untuk Esai 2 (semua esai selesai dinilai)
        $resSemua = $this->actingAs($this->admin)->post(route('admin.penilaian-esai.simpan', $percobaan), [
            'penilaian' => [
                $jEsai2->id => [
                    'poin' => 20,
                    'catatan' => 'Sempurna',
                ],
            ],
        ]);
        $resSemua->assertRedirect(route('admin.penilaian-esai.index'));

        $percobaan->refresh();
        $this->assertFalse((bool) $percobaan->esai_menunggu_nilai);
        // Total poin: 60 (PG) + 15 (Esai 1) + 20 (Esai 2) = 95 / 100 = 95.00
        $this->assertEquals(95.00, (float) $percobaan->nilai_akhir);
        // Status kelulusan TIDAK BOLEH berubah
        $this->assertEquals('lulus', $percobaan->status_kelulusan);
    }

    /**
     * 3. Poin di luar rentang (kurang dari 0 atau melebihi bobot) ditolak lewat Form Request dengan pesan ramah.
     */
    public function test_poin_di_luar_rentang_ditolak_dengan_pesan_ramah(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Rentang']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai Rentang',
            'bobot_poin' => 15,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $jEsai = $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => 'Jawaban',
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
        ]);

        // Kirim poin 25 (melebihi bobot 15)
        $resLebih = $this->actingAs($this->admin)->post(route('admin.penilaian-esai.simpan', $percobaan), [
            'penilaian' => [
                $jEsai->id => [
                    'poin' => 25,
                ],
            ],
        ]);
        $resLebih->assertSessionHasErrors("penilaian.{$jEsai->id}.poin");

        // Kirim poin negatif -5
        $resMinus = $this->actingAs($this->admin)->post(route('admin.penilaian-esai.simpan', $percobaan), [
            'penilaian' => [
                $jEsai->id => [
                    'poin' => -5,
                ],
            ],
        ]);
        $resMinus->assertSessionHasErrors("penilaian.{$jEsai->id}.poin");
    }

    /**
     * 4. Jawaban esai berisi <script> atau tag HTML tampil ter-escape dengan baris baru tetap terbaca.
     */
    public function test_jawaban_esai_berisi_script_tampil_ter_escape(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Kuis Escape']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Pertanyaan',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $xssTeks = "<script>alert('XSS')</script>\nBaris Baru Penjelasan";
        $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => $xssTeks,
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.penilaian-esai.show', $percobaan));
        $res->assertOk();
        // assertSee tanpa $escape=false akan meng-escape input sebelum memeriksa, membuktikan versi ter-escape muncul di HTML
        $res->assertSee("<script>alert('XSS')</script>");
        // assertDontSee dengan $escape=false memastikan tag script mentah tidak ada di HTML
        $res->assertDontSee("<script>alert('XSS')</script>", false);
        $res->assertSee('Baris Baru Penjelasan');
    }

    /**
     * 5. Otorisasi: Siswa dilarang (403) dan tamu diarahkan ke login.
     */
    public function test_siswa_dilarang_akses_dan_tamu_diarahkan_ke_masuk(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Kuis Otorisasi']);
        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        // Tamu
        $this->get(route('admin.penilaian-esai.index'))->assertRedirect(route('masuk'));
        $this->get(route('admin.penilaian-esai.show', $percobaan))->assertRedirect(route('masuk'));

        // Siswa
        $this->actingAs($this->siswa)->get(route('admin.penilaian-esai.index'))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('admin.penilaian-esai.show', $percobaan))->assertForbidden();
    }

    /**
     * 6. Koreksi nilai esai: mencatat riwayat_nilai_esai, menghitung ulang nilai_akhir,
     * status kelulusan tetap, dan nilai tertinggi ikut terbarui.
     */
    public function test_koreksi_nilai_menghitung_ulang_nilai_akhir_dan_mencatat_riwayat(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Koreksi']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai Koreksi',
            'bobot_poin' => 50,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => null,
            'nilai_akhir' => 60.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $jEsai = $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => 'Jawaban Awal',
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
            'poin_diperoleh' => 30.00, // 30/50 = 60.00
            'catatan_guru' => 'Catatan awal',
            'dinilai_pada' => now()->subDay(),
        ]);

        // Admin melakukan koreksi nilai dari 30 menjadi 45 (90.00)
        $res = $this->actingAs($this->admin)->post(route('admin.penilaian-esai.simpan', $percobaan), [
            'penilaian' => [
                $jEsai->id => [
                    'poin' => 45,
                    'catatan' => 'Catatan setelah sanggahan',
                ],
            ],
        ]);
        $res->assertRedirect(route('admin.penilaian-esai.index'));

        $percobaan->refresh();
        $this->assertEquals(90.00, (float) $percobaan->nilai_akhir);

        // Cek pencatatan di riwayat_nilai_esai
        $this->assertDatabaseHas('riwayat_nilai_esai', [
            'jawaban_siswa_id' => $jEsai->id,
            'admin_id' => $this->admin->id,
            'poin_lama' => '30.00',
            'poin_baru' => '45.00',
            'catatan_lama' => 'Catatan awal',
            'catatan_baru' => 'Catatan setelah sanggahan',
        ]);

        // Ambil nilai tertinggi memakai nilai_akhir yang baru
        $nilaiTertinggi = $this->penilaianService->ambilNilaiTertinggi($kuis, $this->siswa);
        $this->assertEquals(90.00, $nilaiTertinggi);
    }

    /**
     * 7. Halaman hasil siswa menampilkan nilai akhir dan catatan guru setelah esai dinilai.
     */
    public function test_hasil_siswa_menampilkan_nilai_akhir_dan_catatan_setelah_dinilai(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest Hasil']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai Akhir',
            'bobot_poin' => 20,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => null,
            'nilai_akhir' => 85.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $percobaan->jawabanSiswa()->create([
            'soal_id' => $soalEsai->id,
            'jawaban_teks' => 'Jawaban Siswa',
            'poin_diperoleh' => 17.00,
            'catatan_guru' => 'Analisis organel sangat komprehensif!',
            'dinilai_pada' => now(),
            'waktu_mulai_soal' => now(),
            'waktu_jawab' => now(),
        ]);

        $res = $this->actingAs($this->siswa)->get(route('siswa.percobaan.hasil', $percobaan));
        $res->assertOk();
        $res->assertSee('85%');
        $res->assertSee('Analisis organel sangat komprehensif!');
        $res->assertDontSee('menunggu penilaian esai');
    }

    /**
     * 8. Lencana jumlah antrean tampil di beranda admin dan navigasi.
     */
    public function test_lencana_jumlah_antrean_benar_di_beranda_admin(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Lencana']);
        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Esai Lencana',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => true,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.beranda'));
        $res->assertOk();
        $res->assertSee('Penilaian Esai');
        $res->assertSee('1'); // Angka badge antrean
    }
}
