<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaEngineKuisTest extends TestCase
{
    use RefreshDatabase;

    private User $siswa1;

    private User $siswa2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->siswa1 = User::factory()->create([
            'nama_lengkap' => 'Siswa Satu',
            'username' => 'siswa_satu',
            'email' => 'siswa1@test.com',
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        $this->siswa2 = User::factory()->create([
            'nama_lengkap' => 'Siswa Dua',
            'username' => 'siswa_dua',
            'email' => 'siswa2@test.com',
            'peran' => 'siswa',
            'aktif' => true,
        ]);
    }

    public function test_alur_penuh_mulai_sampai_hasil_dan_kunci_tidak_bocor_di_html(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $kuis = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Pretest Pengetahuan Awal',
            'batas_waktu_per_soal' => 60,
            'status_terbit' => true,
        ]);

        $soalPg = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa itu sel?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);
        $pBenar = PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Unit Terkecil', 'apakah_benar' => true, 'urutan' => 1]);
        $pSalah = PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Kumpulan Jaringan', 'apakah_benar' => false, 'urutan' => 2]);

        $soalEsai = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Jelaskan teori sel!',
            'bobot_poin' => 20,
            'urutan' => 2,
        ]);

        $this->actingAs($this->siswa1);

        // 1. Buka pengantar kuis
        $resPengantar = $this->get(route('siswa.kuis.show', $kuis));
        $resPengantar->assertOk();
        $resPengantar->assertSee('Pretest Pengetahuan Awal');
        $resPengantar->assertSee('Mulai Kerjakan Kuis');

        // 2. Klik mulai kuis
        $resMulai = $this->post(route('siswa.kuis.mulai', $kuis));
        $resMulai->assertRedirect();

        $percobaan = PercobaanKuis::where('kuis_id', $kuis->id)->where('user_id', $this->siswa1->id)->first();
        $this->assertNotNull($percobaan);
        $this->assertEquals(1, $percobaan->nomor_percobaan);
        $this->assertEquals('sedang_mengerjakan', $percobaan->status_pengerjaan);

        // Pastikan esai berada di akhir urutan
        $this->assertSame([$soalPg->id, $soalEsai->id], $percobaan->urutan_soal_teracak);

        // 3. Buka halaman soal pertama (PG)
        $resSoal1 = $this->get(route('siswa.percobaan.soal', $percobaan));
        $resSoal1->assertOk();
        $resSoal1->assertSee('Apa itu sel?');
        $resSoal1->assertSee('Unit Terkecil');

        // HTML TIDAK boleh membocorkan kunci (tidak ada kata 'apakah_benar', 'kunci', atau 'true' pada opsi)
        $kontenHtml = $resSoal1->getContent();
        $this->assertStringNotContainsString('apakah_benar', $kontenHtml);
        $this->assertStringNotContainsString('data-benar', $kontenHtml);
        $this->assertStringNotContainsString('Kunci Benar', $kontenHtml);

        // 4. Jawab soal 1 (PG benar)
        Carbon::setTestNow('2026-10-11 08:00:20'); // 20 detik kemudian
        $resJawab1 = $this->post(route('siswa.percobaan.jawab', [$percobaan, $soalPg]), [
            'pilihan_jawaban_id' => $pBenar->id,
        ]);
        $resJawab1->assertRedirect(route('siswa.percobaan.soal', $percobaan));

        // 5. Buka halaman soal 2 (Esai)
        $resSoal2 = $this->get(route('siswa.percobaan.soal', $percobaan));
        $resSoal2->assertOk();
        $resSoal2->assertSee('Jelaskan teori sel!');
        $resSoal2->assertSee('Soal esai tidak dibatasi waktu');

        // 6. Jawab soal 2 (Esai - soal terakhir)
        $resJawab2 = $this->post(route('siswa.percobaan.jawab', [$percobaan, $soalEsai]), [
            'jawaban_teks' => 'Sel adalah unit dasar kehidupan yang ditemukan oleh Robert Hooke.',
        ]);
        // Otomatis diserahkan dan diarahkan ke halaman hasil
        $resJawab2->assertRedirect(route('siswa.percobaan.hasil', $percobaan));

        // 7. Buka halaman hasil
        $resHasil = $this->get(route('siswa.percobaan.hasil', $percobaan));
        $resHasil->assertOk();
        $resHasil->assertSee('Pretest selesai, ini hanya untuk mengetahui pengetahuan awalmu');
        $resHasil->assertSee('Nilai akhir: menunggu penilaian esai');
        // Pretest TIDAK menampilkan lencana LULUS / GAGAL
        $resHasil->assertDontSee('LULUS');
        $resHasil->assertDontSee('BELUM LULUS');

        // Percobaan selesai tidak bisa dijawab lagi (diarahkan ke hasil)
        $resCobaJawabLagi = $this->get(route('siswa.percobaan.soal', $percobaan));
        $resCobaJawabLagi->assertRedirect(route('siswa.percobaan.hasil', $percobaan));
    }

    public function test_refresh_soal_melanjutkan_sisa_waktu_server_dan_jawaban_terlambat_dikosongkan(): void
    {
        Carbon::setTestNow('2026-10-11 09:00:00');

        $kuis = Kuis::create([
            'tipe_kuis' => 'pretest',
            'judul' => 'Kuis Timer',
            'batas_waktu_per_soal' => 60,
            'status_terbit' => true,
        ]);
        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal Cepat',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);
        $pBenar = PilihanJawaban::create(['soal_id' => $soal->id, 'teks_pilihan' => 'Benar', 'apakah_benar' => true, 'urutan' => 1]);

        $this->actingAs($this->siswa1);
        $this->post(route('siswa.kuis.mulai', $kuis));
        $percobaan = PercobaanKuis::where('kuis_id', $kuis->id)->first();

        // Buka soal pertama pada detik 0 -> sisaDetik harus 60
        $res1 = $this->get(route('siswa.percobaan.soal', $percobaan));
        $res1->assertOk();
        $res1->assertSee('sisaDetik: 60', false);

        // Majukan waktu 25 detik lalu refresh halaman -> sisaDetik harus 35 (bukan mengulang 60)
        Carbon::setTestNow('2026-10-11 09:00:25');
        $resRefresh = $this->get(route('siswa.percobaan.soal', $percobaan));
        $resRefresh->assertOk();
        $resRefresh->assertSee('sisaDetik: 35', false);

        // Majukan waktu melewati 60 + toleransi 5 detik (misal: detik ke-70) lalu submit jawaban
        Carbon::setTestNow('2026-10-11 09:01:10');
        $this->post(route('siswa.percobaan.jawab', [$percobaan, $soal]), [
            'pilihan_jawaban_id' => $pBenar->id,
        ]);

        // Jawaban terbukti dikosongkan dan poin 0
        $jawaban = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)->where('soal_id', $soal->id)->first();
        $this->assertNull($jawaban->pilihan_jawaban_id);
        $this->assertEquals(0.00, $jawaban->poin_diperoleh);
    }

    public function test_menjawab_soal_yang_bukan_soal_aktif_ditolak(): void
    {
        Carbon::setTestNow('2026-10-11 10:00:00');

        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Kuis Urutan']);
        $soal1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S1', 'bobot_poin' => 5, 'urutan' => 1]);
        $soal2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S2', 'bobot_poin' => 5, 'urutan' => 2]);
        PilihanJawaban::create(['soal_id' => $soal1->id, 'teks_pilihan' => 'O1', 'apakah_benar' => true, 'urutan' => 1]);
        PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'O2', 'apakah_benar' => true, 'urutan' => 1]);

        $this->actingAs($this->siswa1);
        $this->post(route('siswa.kuis.mulai', $kuis));
        $percobaan = PercobaanKuis::where('kuis_id', $kuis->id)->first();

        // Soal aktif saat ini adalah soal 1. Mencoba langsung submit soal 2 harus ditolak 403
        $response = $this->post(route('siswa.percobaan.jawab', [$percobaan, $soal2]), [
            'pilihan_jawaban_id' => 1,
        ]);
        $response->assertForbidden();
    }

    public function test_percobaan_milik_siswa_lain_tidak_bisa_diakses(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Kuis Isolasi']);
        $percobaanSiswa1 = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'sedang_mengerjakan',
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
        ]);

        // Siswa 2 mencoba membuka soal atau hasil milik Siswa 1 -> ditolak 403 oleh Policy
        $resSoal = $this->actingAs($this->siswa2)->get(route('siswa.percobaan.soal', $percobaanSiswa1));
        $resSoal->assertForbidden();

        $resHasil = $this->actingAs($this->siswa2)->get(route('siswa.percobaan.hasil', $percobaanSiswa1));
        $resHasil->assertForbidden();
    }

    public function test_kuis_terkunci_dan_kesempatan_ketiga_serta_pretest_ganda_ditolak(): void
    {
        // 1. Kuis terkunci (pretest belum dikerjakan)
        $pretest = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest']);
        $bab = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);
        $kuisBab = Kuis::create([
            'bab_id' => $bab->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Bab 1',
            'maksimal_kesempatan' => 2,
        ]);

        $this->actingAs($this->siswa1);
        $resKuisTerkunci = $this->post(route('siswa.kuis.mulai', $kuisBab));
        $resKuisTerkunci->assertRedirect(route('siswa.kuis.show', $kuisBab));
        $this->assertNotNull(session('galat'));

        // 2. Pretest diselesaikan sekali -> pretest tidak bisa diulang
        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $resPretestGanda = $this->post(route('siswa.kuis.mulai', $pretest));
        $resPretestGanda->assertRedirect(route('siswa.kuis.show', $pretest));
        $this->assertNotNull(session('galat'));

        // 3. Kuis bab: kesempatan ke-3 ditolak (hanya 2 kesempatan)
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus',
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus_lanjut',
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $resKe3 = $this->post(route('siswa.kuis.mulai', $kuisBab));
        $resKe3->assertRedirect(route('siswa.kuis.show', $kuisBab));
        $this->assertNotNull(session('galat'));
    }

    public function test_klik_mulai_dua_kali_tidak_membuat_percobaan_ganda(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest Idempoten']);
        $soal = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S1', 'bobot_poin' => 10, 'urutan' => 1]);

        $this->actingAs($this->siswa1);

        // Klik mulai pertama
        $this->post(route('siswa.kuis.mulai', $kuis))->assertRedirect();
        // Klik mulai kedua (misal: klik cepat atau tab ganda)
        $this->post(route('siswa.kuis.mulai', $kuis))->assertRedirect();

        // Jumlah baris percobaan kuis tetap 1
        $this->assertEquals(1, PercobaanKuis::where('kuis_id', $kuis->id)->where('user_id', $this->siswa1->id)->count());
    }

    public function test_hasil_menampilkan_lencana_status_benar_lulus_belum_lulus_dan_belum_lulus_lanjut(): void
    {
        $bab = Bab::create(['judul' => 'Bab Evaluasi', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create([
            'bab_id' => $bab->id,
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Status',
            'nilai_lulus' => 65.00,
            'maksimal_kesempatan' => 2,
        ]);

        $this->actingAs($this->siswa1);

        // Kasus 1: Lulus
        $pLulus = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'nilai_otomatis' => 80.00,
            'nilai_akhir' => 80.00,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $resLulus = $this->get(route('siswa.percobaan.hasil', $pLulus));
        $resLulus->assertOk();
        $resLulus->assertSee('LULUS');
        $resLulus->assertDontSee('Coba Lagi');

        // Kasus 2: Belum Lulus (percobaan 1, masih ada kesempatan) -> tombol coba lagi muncul
        $pBelumLulus = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus',
            'nilai_otomatis' => 50.00,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $resBelum = $this->get(route('siswa.percobaan.hasil', $pBelumLulus));
        $resBelum->assertOk();
        $resBelum->assertSee('BELUM LULUS');

        // Kasus 3: Belum Lulus Lanjut (kesempatan habis)
        $pLanjut = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 3,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'belum_lulus_lanjut',
            'nilai_otomatis' => 50.00,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);
        $resLanjut = $this->get(route('siswa.percobaan.hasil', $pLanjut));
        $resLanjut->assertOk();
        $resLanjut->assertSee('BELUM LULUS (DAPAT MELANJUTKAN)');
    }
}
