<?php

namespace Tests\Unit;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\Materi;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\ProgresMateri;
use App\Models\Soal;
use App\Models\User;
use App\Services\BukaAksesService;
use App\Services\PelacakAksesService;
use App\Services\PenilaianKuisService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LogikaIntiTest extends TestCase
{
    use DatabaseTransactions;

    protected PenilaianKuisService $penilaianService;

    protected BukaAksesService $bukaAksesService;

    protected PelacakAksesService $pelacakService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->penilaianService = new PenilaianKuisService;
        $this->bukaAksesService = new BukaAksesService;
        $this->pelacakService = new PelacakAksesService;
    }

    // ==========================================
    // BAGIAN 1: PenilaianKuisService
    // ==========================================

    public function test_acak_urutan_soal_dengan_esai_selalu_di_akhir(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Variasi Soal',
            'acak_soal' => true,
        ]);

        $pg1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'PG 1', 'urutan' => 1]);
        $esai1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'esai', 'pertanyaan' => 'Esai 1', 'urutan' => 2]);
        $isian1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'isian', 'pertanyaan' => 'Isian 1', 'urutan' => 3]);
        $esai2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'esai', 'pertanyaan' => 'Esai 2', 'urutan' => 4]);
        $pg2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'PG 2', 'urutan' => 5]);

        $urutan = $this->penilaianService->acakUrutanSoal($kuis);

        $this->assertCount(5, $urutan);
        // Dua soal terakhir harus berupa esai
        $duaTerakhir = array_slice($urutan, -2);
        $this->assertTrue(in_array($esai1->id, $duaTerakhir) && in_array($esai2->id, $duaTerakhir));
        // Tiga soal pertama adalah PG dan Isian
        $tigaAwal = array_slice($urutan, 0, 3);
        $this->assertTrue(! in_array($esai1->id, $tigaAwal) && ! in_array($esai2->id, $tigaAwal));

        // Jika acak_soal false, urutkan sesuai urutan asli tetapi esai tetap di akhir
        $kuis->update(['acak_soal' => false]);
        $urutanTetap = $this->penilaianService->acakUrutanSoal($kuis);
        $this->assertEquals([$pg1->id, $isian1->id, $pg2->id, $esai1->id, $esai2->id], $urutanTetap);
    }

    public function test_mulai_atau_lanjut_percobaan_mengembalikan_percobaan_menggantung(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Lanjut']);
        $soal = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 1']);

        // Mulai pertama
        $percobaan1 = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->assertEquals('sedang_mengerjakan', $percobaan1->status_pengerjaan);
        $this->assertEquals(1, $percobaan1->nomor_percobaan);

        // Panggil lagi saat masih sedang_mengerjakan -> mengembalikan percobaan yang sama, tidak membuat baru
        $percobaanLanjut = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->assertEquals($percobaan1->id, $percobaanLanjut->id);
    }

    public function test_batas_kesempatan_percobaan_ketiga_ditolak_dan_pretest_hanya_sekali(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis 2 Kesempatan', 'maksimal_kesempatan' => 2]);

        $this->assertTrue($this->penilaianService->bisaMulaiPercobaan($kuis, $siswa));

        // Percobaan 1 selesai
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        $this->assertTrue($this->penilaianService->bisaMulaiPercobaan($kuis, $siswa));

        // Percobaan 2 selesai
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 2,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        // Percobaan 3 harus ditolak
        $this->assertFalse($this->penilaianService->bisaMulaiPercobaan($kuis, $siswa));

        // Pretest: kesempatan maksimal selalu 1
        $pretest = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest']);
        $this->assertTrue($this->penilaianService->bisaMulaiPercobaan($pretest, $siswa));

        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        $this->assertFalse($this->penilaianService->bisaMulaiPercobaan($pretest, $siswa));
    }

    public function test_mulai_soal_menulis_waktu_sekali_dan_refresh_tidak_mengulang_timer(): void
    {
        Carbon::setTestNow('2026-10-11 10:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Timer']);
        $soal1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 1']);
        $soal2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 2']);

        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);

        // Mulai soal pertama
        $jawaban1 = $this->penilaianService->mulaiSoal($percobaan, $soal1);
        $this->assertEquals('2026-10-11 10:00:00', $jawaban1->waktu_mulai_soal->format('Y-m-d H:i:s'));

        // Refresh halaman setelah 20 detik
        Carbon::setTestNow('2026-10-11 10:00:20');
        $jawabanRefresh = $this->penilaianService->mulaiSoal($percobaan, $soal1);
        // Waktu mulai tidak boleh berubah
        $this->assertEquals('2026-10-11 10:00:00', $jawabanRefresh->waktu_mulai_soal->format('Y-m-d H:i:s'));

        // Tidak boleh langsung loncat ke soal 2 sebelum soal 1 difinalkan/dijawab
        $this->expectException(\DomainException::class);
        $this->penilaianService->mulaiSoal($percobaan, $soal2);
    }

    public function test_simpan_jawaban_pg_benar_dan_kirim_ganda_ditolak(): void
    {
        Carbon::setTestNow('2026-10-11 10:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis PG', 'batas_waktu_per_soal' => 60]);
        $soal = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal PG', 'bobot_poin' => 5]);
        $pilihanBenar = PilihanJawaban::create(['soal_id' => $soal->id, 'teks_pilihan' => 'Benar', 'apakah_benar' => true]);
        $pilihanSalah = PilihanJawaban::create(['soal_id' => $soal->id, 'teks_pilihan' => 'Salah', 'apakah_benar' => false]);

        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->penilaianService->mulaiSoal($percobaan, $soal);

        // Jawab setelah 30 detik (masih valid dalam batas 60 detik)
        Carbon::setTestNow('2026-10-11 10:00:30');
        $jawaban = $this->penilaianService->simpanJawaban($percobaan, $soal, ['pilihan_jawaban_id' => $pilihanBenar->id]);

        $this->assertEquals(5.00, $jawaban->poin_diperoleh);

        // Pengiriman kedua untuk PG/isian harus ditolak dan tidak mengubah poin
        $this->expectException(\DomainException::class);
        $this->penilaianService->simpanJawaban($percobaan, $soal, ['pilihan_jawaban_id' => $pilihanSalah->id]);
    }

    public function test_jawaban_isian_dengan_beberapa_kunci_dan_normalisasi(): void
    {
        Carbon::setTestNow('2026-10-11 10:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Isian']);
        $soal = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'isian', 'pertanyaan' => 'Organel sel?', 'bobot_poin' => 10]);

        // Dua kunci jawaban yang diterima: 'mitokondria' dan 'badan mitokondria'
        PilihanJawaban::create(['soal_id' => $soal->id, 'teks_pilihan' => 'mitokondria', 'apakah_benar' => true]);
        PilihanJawaban::create(['soal_id' => $soal->id, 'teks_pilihan' => 'badan mitokondria', 'apakah_benar' => true]);

        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->penilaianService->mulaiSoal($percobaan, $soal);

        // Siswa menjawab dengan huruf besar, spasi tepi, dan spasi ganda di tengah: "  Badan   Mitokondria  "
        Carbon::setTestNow('2026-10-11 10:00:15');
        $jawaban = $this->penilaianService->simpanJawaban($percobaan, $soal, ['jawaban_teks' => '  Badan   Mitokondria  ']);

        $this->assertEquals(10.00, $jawaban->poin_diperoleh);
    }

    public function test_validasi_timer_server_jawaban_lewat_batas_waktu_dikosongkan_dan_esai_bebas_timer(): void
    {
        Carbon::setTestNow('2026-10-11 10:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);
        // Batas waktu 60 detik + toleransi 5 detik = maksimal 65 detik
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Batas Waktu', 'batas_waktu_per_soal' => 60]);
        $soalPg = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 1', 'bobot_poin' => 4]);
        $pilihanBenar = PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'A', 'apakah_benar' => true]);
        $soalEsai = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'esai', 'pertanyaan' => 'Soal Esai', 'bobot_poin' => 10]);

        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);

        // Soal PG dimulai
        $this->penilaianService->mulaiSoal($percobaan, $soalPg);

        // Dijawab pada detik ke-66 (lewat dari 60 + 5)
        Carbon::setTestNow('2026-10-11 10:01:06');
        $jawabanPg = $this->penilaianService->simpanJawaban($percobaan, $soalPg, ['pilihan_jawaban_id' => $pilihanBenar->id]);

        // Poin harus 0 dan jawaban pilihan_jawaban_id dikosongkan/dianggap kosong
        $this->assertEquals(0.00, $jawabanPg->poin_diperoleh);
        $this->assertNull($jawabanPg->pilihan_jawaban_id);

        // Soal Esai dimulai
        $this->penilaianService->mulaiSoal($percobaan, $soalEsai);

        // Esai dijawab 1 jam kemudian -> tetap sah karena esai tanpa batas waktu
        Carbon::setTestNow('2026-10-11 11:00:00');
        $jawabanEsai = $this->penilaianService->simpanJawaban($percobaan, $soalEsai, ['jawaban_teks' => 'Penjelasan mendalam tentang kloroplas...']);
        $this->assertEquals('Penjelasan mendalam tentang kloroplas...', $jawabanEsai->jawaban_teks);

        // Esai boleh diperbarui selama belum diserahkan
        Carbon::setTestNow('2026-10-11 11:05:00');
        $jawabanEsaiUpdate = $this->penilaianService->simpanJawaban($percobaan, $soalEsai, ['jawaban_teks' => 'Revisi penjelasan...']);
        $this->assertEquals('Revisi penjelasan...', $jawabanEsaiUpdate->jawaban_teks);
    }

    public function test_serahkan_percobaan_aturan_lulus_dan_status_belum_lulus_lanjut(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Penentuan',
            'nilai_lulus' => 65.00,
            'maksimal_kesempatan' => 2,
        ]);

        $soal1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S1', 'bobot_poin' => 10]);
        $soal2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S2', 'bobot_poin' => 10]);
        $p1 = PilihanJawaban::create(['soal_id' => $soal1->id, 'teks_pilihan' => 'B1', 'apakah_benar' => true]);
        $p2 = PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'B2', 'apakah_benar' => true]);

        // Percobaan 1: hanya jawab 1 soal benar (10 dari 20 = 50.00%) -> belum lulus
        $percobaan1 = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->penilaianService->mulaiSoal($percobaan1, $soal1);
        $this->penilaianService->simpanJawaban($percobaan1, $soal1, ['pilihan_jawaban_id' => $p1->id]);

        $selesai1 = $this->penilaianService->serahkanPercobaan($percobaan1);
        $this->assertEquals('selesai', $selesai1->status_pengerjaan);
        $this->assertEquals(50.00, $selesai1->nilai_otomatis);
        $this->assertEquals(50.00, $selesai1->nilai_akhir);
        $this->assertEquals('belum_lulus', $selesai1->status_kelulusan);

        // Panggilan serahkan kedua idempoten (tidak mengubah hasil)
        $idempoten = $this->penilaianService->serahkanPercobaan($percobaan1);
        $this->assertEquals(50.00, $idempoten->nilai_akhir);

        // Percobaan 2: jawab 1 soal benar (50.00%) -> karena kesempatan habis (2 terpakai), status menjadi belum_lulus_lanjut
        $percobaan2 = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->penilaianService->mulaiSoal($percobaan2, $soal1);
        $this->penilaianService->simpanJawaban($percobaan2, $soal1, ['pilihan_jawaban_id' => $p1->id]);

        $selesai2 = $this->penilaianService->serahkanPercobaan($percobaan2);
        $this->assertEquals('belum_lulus_lanjut', $selesai2->status_kelulusan);
    }

    public function test_penilaian_esai_memperbarui_nilai_akhir_tanpa_mengubah_status_kelulusan(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Campuran',
            'nilai_lulus' => 65.00,
        ]);

        $soalPg = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'PG', 'bobot_poin' => 10]);
        $soalEsai = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'esai', 'pertanyaan' => 'Esai', 'bobot_poin' => 10]);
        $pPg = PilihanJawaban::create(['soal_id' => $soalPg->id, 'teks_pilihan' => 'Benar', 'apakah_benar' => true]);

        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        // Jawab PG benar (10 dari 10 soal otomatis = 100%) -> LULUS
        $this->penilaianService->mulaiSoal($percobaan, $soalPg);
        $this->penilaianService->simpanJawaban($percobaan, $soalPg, ['pilihan_jawaban_id' => $pPg->id]);

        // Jawab Esai
        $this->penilaianService->mulaiSoal($percobaan, $soalEsai);
        $this->penilaianService->simpanJawaban($percobaan, $soalEsai, ['jawaban_teks' => 'Jawaban esai siswa']);

        // Serahkan kuis
        $diserahkan = $this->penilaianService->serahkanPercobaan($percobaan);

        $this->assertEquals('lulus', $diserahkan->status_kelulusan);
        $this->assertEquals(100.00, $diserahkan->nilai_otomatis);
        $this->assertTrue($diserahkan->esai_menunggu_nilai);
        $this->assertNull($diserahkan->nilai_akhir); // null selama esai menunggu

        // Guru menilai esai: bobot 10, diberi 5 poin
        $jawabanEsai = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)->where('soal_id', $soalEsai->id)->first();
        $this->penilaianService->nilaiEsai($jawabanEsai, 5.00, 'Cukup bagus');

        $percobaan->refresh();
        $this->assertFalse($percobaan->esai_menunggu_nilai);
        // Total poin: 10 + 5 = 15 dari 20 = 75.00%
        $this->assertEquals(75.00, $percobaan->nilai_akhir);
        // Status kelulusan TIDAK BOLEH BERUBAH
        $this->assertEquals('lulus', $percobaan->status_kelulusan);

        // Guru coba beri nilai melebihi bobot (15 dari 10) -> harus ditolak
        $this->expectException(\InvalidArgumentException::class);
        $this->penilaianService->nilaiEsai($jawabanEsai, 15.00, 'Melebihi bobot');
    }

    public function test_ambil_nilai_tertinggi_mengabaikan_percobaan_dibatalkan(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Tertinggi']);

        // Percobaan 1: nilai 80.00 (tapi dibatalkan oleh admin)
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => true,
            'nilai_akhir' => 80.00,
            'urutan_soal_teracak' => [],
        ]);

        // Percobaan 2: nilai 60.00 (tidak dibatalkan)
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 2,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'nilai_akhir' => 60.00,
            'urutan_soal_teracak' => [],
        ]);

        // Percobaan 3: esai belum dinilai (nilai_akhir null, nilai_otomatis 70.00)
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 3,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'esai_menunggu_nilai' => true,
            'nilai_otomatis' => 70.00,
            'nilai_akhir' => null,
            'urutan_soal_teracak' => [],
        ]);

        // Nilai tertinggi harus 70.00 (dari percobaan 3; percobaan 1 diabaikan karena dibatalkan)
        $tertinggi = $this->penilaianService->ambilNilaiTertinggi($kuis, $siswa);
        $this->assertEquals(70.00, $tertinggi);
    }

    // ==========================================
    // BAGIAN 2: BukaAksesService
    // ==========================================

    public function test_alur_buka_akses_berurutan_termasuk_bab_tanpa_kuis_dan_kuis_hanya_esai(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);

        $pretest = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest Awal']);

        $bab1 = Bab::create(['urutan' => 1, 'judul' => 'Bab 1', 'status_terbit' => true]);
        $kuisBab1 = Kuis::create(['bab_id' => $bab1->id, 'tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis 1']);
        Soal::create(['kuis_id' => $kuisBab1->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'S1']);

        // Bab 2 terbit, tanpa kuis (hanya materi)
        $bab2 = Bab::create(['urutan' => 2, 'judul' => 'Bab 2 Tanpa Kuis', 'status_terbit' => true]);
        $materiBab2 = Materi::create(['bab_id' => $bab2->id, 'judul' => 'Slide 2', 'tipe' => 'pdf', 'urutan' => 1]);

        // Bab 3 tidak terbit (harus dilewati oleh urutan)
        $bab3Draft = Bab::create(['urutan' => 3, 'judul' => 'Bab Draf', 'status_terbit' => false]);

        // Bab 4 terbit, kuis hanya berisi esai
        $bab4 = Bab::create(['urutan' => 4, 'judul' => 'Bab 4 Esai Saja', 'status_terbit' => true]);
        $kuisBab4 = Kuis::create(['bab_id' => $bab4->id, 'tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis 4 Esai']);
        Soal::create(['kuis_id' => $kuisBab4->id, 'tipe_soal' => 'esai', 'pertanyaan' => 'Esai Saja']);

        $posttest = Kuis::create(['tipe_kuis' => 'posttest', 'judul' => 'Posttest Akhir']);

        // 1. Kondisi awal: Pretest terbuka, Bab 1 terkunci
        $this->assertTrue($this->bukaAksesService->apakahKuisTerbuka($pretest, $siswa));
        $this->assertFalse($this->bukaAksesService->apakahBabTerbuka($bab1, $siswa));

        // 2. Pretest selesai -> Bab 1 terbuka, Pretest tertutup
        PercobaanKuis::create([
            'kuis_id' => $pretest->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        $this->assertTrue($this->bukaAksesService->apakahBabTerbuka($bab1, $siswa));
        $this->assertFalse($this->bukaAksesService->apakahKuisTerbuka($pretest, $siswa));

        // Bab 2 masih terkunci karena kuis Bab 1 belum lulus
        $this->assertFalse($this->bukaAksesService->apakahBabTerbuka($bab2, $siswa));

        // 3. Kuis Bab 1 lulus -> Bab 2 terbuka
        PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        $this->assertTrue($this->bukaAksesService->apakahBabTerbuka($bab2, $siswa));

        // Bab 4 masih terkunci karena Bab 2 (tanpa kuis) belum semua materi dibuka
        $this->assertFalse($this->bukaAksesService->apakahBabTerbuka($bab4, $siswa));

        // 4. Buka materi Bab 2 -> Bab 4 terbuka (Bab 3 dilewati karena status_terbit false)
        ProgresMateri::create([
            'user_id' => $siswa->id,
            'materi_id' => $materiBab2->id,
            'sudah_dibuka' => true,
            'waktu_buka' => now(),
        ]);

        $this->assertTrue($this->bukaAksesService->apakahBabTerbuka($bab4, $siswa));

        // Posttest masih terkunci
        $this->assertFalse($this->bukaAksesService->apakahKuisTerbuka($posttest, $siswa));

        // 5. Kuis Bab 4 (hanya esai) diserahkan -> Posttest terbuka
        PercobaanKuis::create([
            'kuis_id' => $kuisBab4->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => null, // kuis tanpa soal otomatis
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);

        $this->assertTrue($this->bukaAksesService->apakahKuisTerbuka($posttest, $siswa));

        // 6. Posttest selesai -> tertutup
        PercobaanKuis::create([
            'kuis_id' => $posttest->id,
            'user_id' => $siswa->id,
            'nomor_percobaan' => 1,
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
        ]);
        $this->assertFalse($this->bukaAksesService->apakahKuisTerbuka($posttest, $siswa));

        // 7. Periksa daftar langkah belajar untuk stepper
        $langkah = $this->bukaAksesService->daftarLangkahBelajar($siswa);
        $this->assertCount(5, $langkah); // Pretest, Bab 1, Bab 2, Bab 4, Posttest
        $this->assertEquals('selesai', $langkah[0]['status']); // Pretest selesai
        $this->assertEquals('selesai', $langkah[4]['status']); // Posttest selesai
    }

    // ==========================================
    // BAGIAN 3: PelacakAksesService (Detak Jantung & Durasi Sesi)
    // ==========================================

    public function test_rekam_detak_jantung_menambah_durasi_hanya_pada_interval_valid(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);

        // 1. Siswa masuk
        $log1 = $this->pelacakService->catatMasuk($siswa);
        $this->assertNotNull($log1);
        $this->assertEquals(0, $log1->durasi_detik);

        // 2. Detak jantung 30 detik kemudian (masih <= 120 detik) -> durasi bertambah 30 detik
        Carbon::setTestNow('2026-10-11 08:00:30');
        $this->pelacakService->rekamDetakJantung($siswa);
        $log1->refresh();
        $this->assertEquals(30, $log1->durasi_detik);

        // 3. Detak jantung 40 detik kemudian -> durasi bertambah 40 menjadi 70 detik
        Carbon::setTestNow('2026-10-11 08:01:10');
        $this->pelacakService->rekamDetakJantung($siswa);
        $log1->refresh();
        $this->assertEquals(70, $log1->durasi_detik);

        // 4. Jeda panjang melewati batas 120 detik (misal: 300 detik kemudian) -> sesi lama ditutup, sesi baru dibuka
        Carbon::setTestNow('2026-10-11 08:06:10');
        $this->pelacakService->rekamDetakJantung($siswa);

        $log1->refresh();
        // Durasi log lama TETAP 70 (jeda panjang 300 detik tidak dihitung)
        $this->assertEquals(70, $log1->durasi_detik);

        // Baris log baru dibuat dengan durasi 0
        $log2 = LogAkses::where('user_id', $siswa->id)->latest('id')->first();
        $this->assertNotEquals($log1->id, $log2->id);
        $this->assertEquals(0, $log2->durasi_detik);

        // 5. Keluar setelah 45 detik pada sesi kedua -> durasi sesi kedua bertambah 45 detik
        Carbon::setTestNow('2026-10-11 08:06:55');
        $this->pelacakService->catatKeluar($siswa);
        $log2->refresh();
        $this->assertEquals(45, $log2->durasi_detik);
    }

    public function test_celah_timer_tiga_kejadian_otomatis_difinalkan_kosong(): void
    {
        Carbon::setTestNow('2026-10-11 09:00:00');

        $siswa = User::factory()->create(['peran' => 'siswa']);
        $kuis = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Tutup Celah Timer', 'batas_waktu_per_soal' => 60]);
        $soal1 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 1', 'bobot_poin' => 5]);
        $soal2 = Soal::create(['kuis_id' => $kuis->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 2', 'bobot_poin' => 5]);
        $p1 = PilihanJawaban::create(['soal_id' => $soal1->id, 'teks_pilihan' => 'Kunci 1', 'apakah_benar' => true]);
        $p2 = PilihanJawaban::create(['soal_id' => $soal2->id, 'teks_pilihan' => 'Kunci 2', 'apakah_benar' => true]);

        // Kejadian 1 & 2: Siswa mulai soal 1, diam > 65 detik (misal 70 detik), lalu minta soal 2
        $percobaan = $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);
        $this->penilaianService->mulaiSoal($percobaan, $soal1);

        Carbon::setTestNow('2026-10-11 09:01:10'); // 70 detik kemudian (lewat dari 60 + 5)
        // Minta soal 2 -> soal 1 harus otomatis difinalkan kosong dan poin 0, sehingga tidak macet
        $jawaban2 = $this->penilaianService->mulaiSoal($percobaan, $soal2);
        $this->assertNotNull($jawaban2);

        $jawaban1 = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)->where('soal_id', $soal1->id)->first();
        $this->assertNotNull($jawaban1->waktu_jawab);
        $this->assertNull($jawaban1->pilihan_jawaban_id);
        $this->assertEquals(0.00, $jawaban1->poin_diperoleh);

        // Kejadian 3: Siswa sedang di soal 2, diam > 65 detik, tutup browser, lalu lanjut percobaan lagi
        Carbon::setTestNow('2026-10-11 09:05:00');
        $this->penilaianService->mulaiAtauLanjutPercobaan($kuis, $siswa);

        $jawaban2 = JawabanSiswa::where('percobaan_kuis_id', $percobaan->id)->where('soal_id', $soal2->id)->first();
        $this->assertNotNull($jawaban2->waktu_jawab);
        $this->assertEquals(0.00, $jawaban2->poin_diperoleh);

        // Kasus siswa diam > 65 detik lalu tetap memaksakan kirim jawaban -> jawaban dibuang dan dinilai kosong
        $kuisPaksa = Kuis::create(['tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis Paksa', 'batas_waktu_per_soal' => 60]);
        $soal3 = Soal::create(['kuis_id' => $kuisPaksa->id, 'tipe_soal' => 'pilihan_ganda', 'pertanyaan' => 'Soal 3', 'bobot_poin' => 5]);
        $p3 = PilihanJawaban::create(['soal_id' => $soal3->id, 'teks_pilihan' => 'Kunci 3', 'apakah_benar' => true]);

        $percobaan2 = $this->penilaianService->mulaiAtauLanjutPercobaan($kuisPaksa, $siswa);
        $this->penilaianService->mulaiSoal($percobaan2, $soal3);

        Carbon::setTestNow('2026-10-11 09:07:00'); // Lewat batas
        $jawabanTerlambat = $this->penilaianService->simpanJawaban($percobaan2, $soal3, ['pilihan_jawaban_id' => $p3->id]);
        $this->assertEquals(0.00, $jawabanTerlambat->poin_diperoleh);
        $this->assertNull($jawabanTerlambat->pilihan_jawaban_id);
    }

    public function test_pelacak_akses_hanya_mencatat_siswa_bukan_admin(): void
    {
        $admin = User::factory()->create(['peran' => 'admin']);

        $logMasuk = $this->pelacakService->catatMasuk($admin);
        $this->assertNull($logMasuk);

        $logDetak = $this->pelacakService->rekamDetakJantung($admin);
        $this->assertNull($logDetak);

        $this->pelacakService->catatKeluar($admin);
        $this->assertDatabaseMissing('log_akses', ['user_id' => $admin->id]);
    }
}
