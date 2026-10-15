<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\JawabanSiswa;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\PercobaanKuis;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminKontenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Admin Irma',
            'username' => 'irma_admin',
            'email' => 'admin_irma@test.com',
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Siswa Budi',
            'username' => 'budi_siswa',
            'email' => 'budi@test.com',
            'peran' => 'siswa',
            'aktif' => true,
        ]);
    }

    public function test_tamu_diarahkan_ke_masuk_dan_siswa_dilarang_akses_admin(): void
    {
        $responseTamu = $this->get(route('admin.bab.index'));
        $responseTamu->assertRedirect(route('masuk'));

        $responseSiswa = $this->actingAs($this->siswa)->get(route('admin.bab.index'));
        $responseSiswa->assertForbidden();
    }

    public function test_crud_bab_dan_toggle_status_terbit_serta_urutan_naik_turun(): void
    {
        $this->actingAs($this->admin);

        // Buat bab 1
        $resStore1 = $this->post(route('admin.bab.store'), [
            'judul' => 'Bab 1 Struktur Sel',
            'deskripsi' => 'Pengenalan organel dan fungsi sel',
            'status_terbit' => '1',
        ]);
        $resStore1->assertRedirect(route('admin.bab.index'));

        // Buat bab 2
        $resStore2 = $this->post(route('admin.bab.store'), [
            'judul' => 'Bab 2 Metabolisme',
            'deskripsi' => 'Enzim dan katabolisme',
            'status_terbit' => '0',
        ]);
        $resStore2->assertRedirect(route('admin.bab.index'));

        $bab1 = Bab::where('judul', 'Bab 1 Struktur Sel')->first();
        $bab2 = Bab::where('judul', 'Bab 2 Metabolisme')->first();

        $this->assertEquals(1, $bab1->urutan);
        $this->assertEquals(2, $bab2->urutan);
        $this->assertTrue($bab1->status_terbit);
        $this->assertFalse($bab2->status_terbit);

        // Toggle terbit
        $this->post(route('admin.bab.terbit', $bab1))->assertRedirect();
        $this->assertFalse($bab1->fresh()->status_terbit);

        // Tukar urutan: bab 2 naik
        $this->post(route('admin.bab.naik', $bab2))->assertRedirect();
        $this->assertEquals(1, $bab2->fresh()->urutan);
        $this->assertEquals(2, $bab1->fresh()->urutan);

        // Bab 2 turun kembali
        $this->post(route('admin.bab.turun', $bab2))->assertRedirect();
        $this->assertEquals(2, $bab2->fresh()->urutan);
        $this->assertEquals(1, $bab1->fresh()->urutan);

        // Edit bab
        $this->put(route('admin.bab.update', $bab1), [
            'judul' => 'Bab 1 Biologi Sel Terpadu',
            'deskripsi' => 'Revisi deskripsi',
            'status_terbit' => '1',
        ])->assertRedirect(route('admin.bab.index'));
        $this->assertEquals('Bab 1 Biologi Sel Terpadu', $bab1->fresh()->judul);
    }

    public function test_upload_pdf_materi_privat_dan_validasi_url_youtube(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);

        $bab = Bab::create(['judul' => 'Bab 1', 'urutan' => 1, 'status_terbit' => true]);

        // Upload PDF valid & URL YouTube valid
        $filePdf = UploadedFile::fake()->createWithContent('modul_sel.pdf', "%PDF-1.4\nKonten modul biologi sel");
        $resStore = $this->post(route('admin.bab.materi.store', $bab), [
            'judul' => 'Modul 1 Sel',
            'tipe' => 'pdf',
            'berkas_pdf' => $filePdf,
            'status_terbit' => '1',
        ]);
        $resStore->assertRedirect(route('admin.bab.materi.index', $bab));

        $materiPdf = Materi::where('judul', 'Modul 1 Sel')->first();
        $this->assertNotNull($materiPdf->berkas_pdf);
        Storage::disk('local')->assertExists($materiPdf->berkas_pdf);

        // Tes materi video YouTube
        $resStoreVideo = $this->post(route('admin.bab.materi.store', $bab), [
            'judul' => 'Video Pembelahan Sel',
            'tipe' => 'youtube',
            'url_youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status_terbit' => '1',
        ]);
        $resStoreVideo->assertRedirect(route('admin.bab.materi.index', $bab));

        $materiVideo = Materi::where('judul', 'Video Pembelahan Sel')->first();
        $this->assertEquals('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $materiVideo->url_youtube);

        // Validasi format non-pdf dan ukuran > 20MB
        $fileSalah = UploadedFile::fake()->create('dokumen.txt', 500, 'text/plain');
        $this->post(route('admin.bab.materi.store', $bab), [
            'judul' => 'Gagal',
            'tipe' => 'pdf',
            'berkas_pdf' => $fileSalah,
        ])->assertSessionHasErrors('berkas_pdf');

        $fileBesar = UploadedFile::fake()->create('besar.pdf', 25000, 'application/pdf');
        $this->post(route('admin.bab.materi.store', $bab), [
            'judul' => 'Gagal Besar',
            'tipe' => 'pdf',
            'berkas_pdf' => $fileBesar,
        ])->assertSessionHasErrors('berkas_pdf');
    }

    public function test_validasi_ramah_menolak_pretest_dan_posttest_ganda(): void
    {
        $this->actingAs($this->admin);

        // Buat pretest pertama
        $this->post(route('admin.kuis.store'), [
            'judul' => 'Pretest Biologi 2026',
            'tipe_kuis' => 'pretest',
            'status_terbit' => '1',
        ])->assertRedirect(route('admin.kuis.index'));

        // Coba buat pretest kedua -> harus ada pesan validasi ramah
        $resGanda = $this->post(route('admin.kuis.store'), [
            'judul' => 'Pretest Cadangan',
            'tipe_kuis' => 'pretest',
            'status_terbit' => '1',
        ]);
        $resGanda->assertSessionHasErrors('tipe_kuis');
        $this->assertStringContainsString('Hanya boleh ada satu pretest', session('errors')->first('tipe_kuis'));
    }

    public function test_validasi_butir_soal_pg_isian_dan_esai(): void
    {
        $this->actingAs($this->admin);

        $kuis = Kuis::create(['judul' => 'Kuis Bab 1', 'tipe_kuis' => 'kuis_bab']);

        // PG tanpa kunci benar -> ditolak
        $this->post(route('admin.kuis.soal.store', $kuis), [
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa fungsi mitokondria?',
            'bobot_poin' => 10,
            'pilihan' => [
                ['teks' => 'Respirasi', 'apakah_benar' => '0'],
                ['teks' => 'Fotosintesis', 'apakah_benar' => '0'],
            ],
        ])->assertSessionHasErrors('pilihan');

        // PG valid
        $resPg = $this->post(route('admin.kuis.soal.store', $kuis), [
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Apa fungsi mitokondria?',
            'bobot_poin' => 10,
            'pilihan' => [
                ['teks' => 'Respirasi sel', 'apakah_benar' => '1'],
                ['teks' => 'Fotosintesis', 'apakah_benar' => '0'],
            ],
        ]);
        $resPg->assertRedirect(route('admin.kuis.soal.index', $kuis));

        // Isian tanpa kunci -> ditolak
        $this->post(route('admin.kuis.soal.store', $kuis), [
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Organel penghasil ATP adalah?',
            'bobot_poin' => 10,
            'kunci_isian' => [''],
        ])->assertSessionHasErrors('kunci_isian');

        // Isian valid
        $this->post(route('admin.kuis.soal.store', $kuis), [
            'tipe_soal' => 'isian',
            'pertanyaan' => 'Organel penghasil ATP adalah?',
            'bobot_poin' => 10,
            'kunci_isian' => ['mitokondria', 'mitokondrion'],
        ])->assertRedirect(route('admin.kuis.soal.index', $kuis));

        // Esai valid (tidak butuh kunci)
        $this->post(route('admin.kuis.soal.store', $kuis), [
            'tipe_soal' => 'esai',
            'pertanyaan' => 'Jelaskan perbedaan sel hewan dan sel tumbuhan secara rinci!',
            'bobot_poin' => 20,
        ])->assertRedirect(route('admin.kuis.soal.index', $kuis));

        $this->assertEquals(3, $kuis->soal()->count());
    }

    public function test_proteksi_integritas_data_penelitian_soal_dan_kuis(): void
    {
        $this->actingAs($this->admin);

        $bab = Bab::create(['judul' => 'Bab Riset', 'urutan' => 1, 'status_terbit' => true]);
        $kuis = Kuis::create(['bab_id' => $bab->id, 'judul' => 'Kuis Riset', 'tipe_kuis' => 'kuis_bab']);
        $soal = Soal::create([
            'kuis_id' => $kuis->id,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => 'Soal terlindungi?',
            'bobot_poin' => 10,
            'urutan' => 1,
        ]);

        $percobaan = PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa->id,
            'nomor_percobaan' => 1,
            'urutan_soal_teracak' => [$soal->id],
            'waktu_mulai' => now(),
            'status_pengerjaan' => 'selesai',
        ]);

        JawabanSiswa::create([
            'percobaan_kuis_id' => $percobaan->id,
            'soal_id' => $soal->id,
            'jawaban_teks' => 'A',
            'waktu_mulai_soal' => now(),
            'poin_diperoleh' => 10,
        ]);

        // 1. Soal tidak boleh dihapus jika sudah ada jawaban
        $resDeleteSoal = $this->delete(route('admin.kuis.soal.destroy', [$kuis, $soal]));
        $resDeleteSoal->assertRedirect();
        $resDeleteSoal->assertSessionHas('galat');
        $this->assertDatabaseHas('soal', ['id' => $soal->id]);

        // 2. Kuis tidak boleh dihapus jika sudah ada percobaan
        $resDeleteKuis = $this->delete(route('admin.kuis.destroy', $kuis));
        $resDeleteKuis->assertRedirect();
        $resDeleteKuis->assertSessionHas('galat');
        $this->assertDatabaseHas('kuis', ['id' => $kuis->id]);

        // 3. Bab tidak boleh dihapus jika kuis di dalamnya ada percobaan
        $resDeleteBab = $this->delete(route('admin.bab.destroy', $bab));
        $resDeleteBab->assertRedirect();
        $resDeleteBab->assertSessionHas('galat');
        $this->assertDatabaseHas('bab', ['id' => $bab->id]);

        // 4. Update soal berjawaban: hanya boleh ubah teks pertanyaan, tipe & bobot tidak berubah
        $this->put(route('admin.kuis.soal.update', [$kuis, $soal]), [
            'pertanyaan' => 'Pertanyaan yang diperbaiki redaksinya',
            'tipe_soal' => 'isian', // berusaha ubah tipe
            'bobot_poin' => 50,     // berusaha ubah bobot
        ])->assertRedirect(route('admin.kuis.soal.index', $kuis));

        $soalSegar = $soal->fresh();
        $this->assertEquals('Pertanyaan yang diperbaiki redaksinya', $soalSegar->pertanyaan);
        $this->assertEquals('pilihan_ganda', $soalSegar->tipe_soal);
        $this->assertEquals(10, $soalSegar->bobot_poin);

        // 5. Perbaikan teks opsi pilihan ganda pada soal berjawaban diizinkan tanpa mengubah kunci benar
        $pilihanAwal = $soal->pilihanJawaban()->create([
            'teks_pilihan' => 'Opsi Lama',
            'apakah_benar' => true,
            'urutan' => 1,
        ]);

        $this->put(route('admin.kuis.soal.update', [$kuis, $soal]), [
            'pertanyaan' => 'Pertanyaan yang diperbaiki redaksinya',
            'pilihan_terkunci' => [
                $pilihanAwal->id => 'Opsi Baru yang Direvisi',
            ],
        ])->assertRedirect(route('admin.kuis.soal.index', $kuis));

        $this->assertEquals('Opsi Baru yang Direvisi', $pilihanAwal->fresh()->teks_pilihan);
        $this->assertTrue($pilihanAwal->fresh()->apakah_benar);
    }

    public function test_hapus_dan_ganti_materi_pdf_menghapus_berkas_fisik_di_storage(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);

        $bab = Bab::create(['judul' => 'Bab PDF', 'urutan' => 1, 'status_terbit' => true]);

        // Upload berkas awal
        $fileAwal = UploadedFile::fake()->createWithContent('modul_awal.pdf', "%PDF-1.4\nKonten awal");
        $this->post(route('admin.bab.materi.store', $bab), [
            'judul' => 'Materi PDF Awal',
            'tipe' => 'pdf',
            'berkas_pdf' => $fileAwal,
        ])->assertRedirect();

        $materi = Materi::where('judul', 'Materi PDF Awal')->first();
        $pathAwal = $materi->berkas_pdf;
        Storage::disk('local')->assertExists($pathAwal);

        // Ganti dengan berkas PDF baru -> berkas lama harus terhapus dari storage
        $fileBaru = UploadedFile::fake()->createWithContent('modul_revisi.pdf', "%PDF-1.4\nKonten revisi");
        $this->put(route('admin.bab.materi.update', [$bab, $materi]), [
            'judul' => 'Materi PDF Revisi',
            'tipe' => 'pdf',
            'berkas_pdf' => $fileBaru,
        ])->assertRedirect();

        Storage::disk('local')->assertMissing($pathAwal);
        $pathBaru = $materi->fresh()->berkas_pdf;
        Storage::disk('local')->assertExists($pathBaru);

        // Hapus materi -> berkas revisi harus terhapus dari storage
        $this->delete(route('admin.bab.materi.destroy', [$bab, $materi]))->assertRedirect();
        Storage::disk('local')->assertMissing($pathBaru);
        $this->assertDatabaseMissing('materi', ['id' => $materi->id]);
    }
}
