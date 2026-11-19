<?php

namespace Tests\Feature;

use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\PilihanJawaban;
use App\Models\Soal;
use App\Models\User;
use App\Services\PenilaianKuisService;
use Database\Seeders\PengaturanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KoreksiIsianDanResetKuisTest extends TestCase
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

    public function test_admin_dapat_melihat_halaman_koreksi_isian(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.koreksi-isian.index'));
        $response->assertOk();
        $response->assertSee('Koreksi Nilai Isian Singkat');
    }

    public function test_siswa_tidak_dapat_mengakses_koreksi_isian(): void
    {
        $response = $this->actingAs($this->siswa)->get(route('admin.koreksi-isian.index'));
        $response->assertForbidden();
    }

    public function test_admin_dapat_mengoreksi_nilai_isian_dan_menghitung_ulang_kuis(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Uji Coba Isian',
            'nilai_lulus' => 65.00,
        ]);

        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Organel sel tempat respirasi adalah?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        PilihanJawaban::create([
            'soal_id' => $soal->id,
            'teks_pilihan' => 'mitokondria',
            'apakah_benar' => true,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'user_id' => $this->siswa->id,
            'kuis_id' => $kuis->id,
            'nomor_percobaan' => 1,
            'urutan_soal_teracak' => [$soal->id],
            'waktu_mulai' => now()->subMinutes(10),
            'waktu_selesai' => now()->subMinutes(5),
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 0.00,
            'nilai_akhir' => 0.00,
            'status_kelulusan' => 'belum_lulus',
        ]);

        $jawaban = JawabanSiswa::create([
            'percobaan_kuis_id' => $percobaan->id,
            'soal_id' => $soal->id,
            'jawaban_teks' => 'mitokondrion',
            'waktu_mulai_soal' => now()->subMinutes(8),
            'waktu_jawab' => now()->subMinutes(7),
            'poin_diperoleh' => 0.00,
        ]);

        // Simpan nilai koreksi (80% = 8 poin)
        $response = $this->actingAs($this->admin)->post(route('admin.koreksi-isian.simpan', $jawaban), [
            'poin' => 8,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('jawaban_siswa', [
            'id' => $jawaban->id,
            'poin_diperoleh' => 8.00,
        ]);

        $percobaan->refresh();
        $this->assertEquals(80.00, (float) $percobaan->nilai_otomatis);
        $this->assertEquals(80.00, (float) $percobaan->nilai_akhir);
        $this->assertEquals('lulus', $percobaan->status_kelulusan);
    }

    public function test_admin_dapat_mereset_pengerjaan_kuis(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Yang Salah Soal',
        ]);

        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Soal keliru',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'user_id' => $this->siswa->id,
            'kuis_id' => $kuis->id,
            'nomor_percobaan' => 1,
            'urutan_soal_teracak' => [$soal->id],
            'waktu_mulai' => now()->subMinutes(10),
            'status_pengerjaan' => 'selesai',
        ]);

        JawabanSiswa::create([
            'percobaan_kuis_id' => $percobaan->id,
            'soal_id' => $soal->id,
            'jawaban_teks' => 'test',
            'waktu_mulai_soal' => now()->subMinutes(8),
            'waktu_jawab' => now()->subMinutes(7),
            'poin_diperoleh' => 0,
        ]);

        $this->assertDatabaseHas('percobaan_kuis', ['id' => $percobaan->id]);

        $response = $this->actingAs($this->admin)->post(route('admin.kuis.reset-pengerjaan', $kuis));
        $response->assertRedirect(route('admin.kuis.index'));
        $response->assertSessionHas('sukses');

        $this->assertDatabaseMissing('percobaan_kuis', ['id' => $percobaan->id]);
        $this->assertDatabaseCount('jawaban_siswa', 0);
    }

    public function test_pencocokan_cerdas_isian_singkat_saat_siswa_menjawab_kalimat_panjang(): void
    {
        $kuis = Kuis::create([
            'tipe_kuis' => 'kuis_bab',
            'judul' => 'Kuis Cerdas Isian',
            'nilai_lulus' => 65.00,
        ]);

        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Organel penghasil energi sel adalah?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        PilihanJawaban::create([
            'soal_id' => $soal->id,
            'teks_pilihan' => 'mitokondria',
            'apakah_benar' => true,
            'urutan' => 1,
        ]);

        $soal2 = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Organel pembentuk protein adalah?',
            'bobot_poin' => 10,
            'urutan' => 2,
        ]);

        PilihanJawaban::create([
            'soal_id' => $soal2->id,
            'teks_pilihan' => 'ribosom',
            'apakah_benar' => true,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'user_id' => $this->siswa->id,
            'kuis_id' => $kuis->id,
            'nomor_percobaan' => 1,
            'urutan_soal_teracak' => [$soal->id, $soal2->id],
            'waktu_mulai' => now()->subMinutes(5),
            'status_pengerjaan' => 'sedang_mengerjakan',
        ]);

        // Siswa menjawab 1 kalimat penuh dengan kata kunci 'mitokondria' di dalamnya
        $jawaban = $this->penilaianService->simpanJawaban($percobaan, $soal, [
            'jawaban_teks' => 'Organel yang berfungsi menghasilkan energi adalah mitokondria.',
        ]);

        // Harus otomatis dinilai benar (10 poin)
        $this->assertEquals(10.00, (float) $jawaban->poin_diperoleh);

        // Uji coba untuk soal kedua dengan jawaban yang salah (tidak mengandung kata kunci)
        $jawabanSalah = $this->penilaianService->simpanJawaban($percobaan, $soal2, [
            'jawaban_teks' => 'Jawabannya adalah mitokondria.',
        ]);
        $this->assertEquals(0.00, (float) $jawabanSalah->poin_diperoleh);
    }
}
