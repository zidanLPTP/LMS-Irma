<?php

namespace Tests\Feature;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\LogAkses;
use App\Models\PercobaanKuis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Tests\TestCase;

class AdminLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siswa1;

    protected User $siswa2;

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

        $this->siswa1 = User::factory()->create([
            'nama_lengkap' => 'Ahmad Fauzi',
            'username' => 'ahmad_fauzi',
            'email' => 'ahmad@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 1',
            'aktif' => true,
        ]);

        $this->siswa2 = User::factory()->create([
            'nama_lengkap' => 'Budi Pratama',
            'username' => 'budi_pratama',
            'email' => 'budi@siswa.test',
            'peran' => 'siswa',
            'kelas' => 'XI MIPA 2',
            'aktif' => true,
        ]);
    }

    /**
     * 1. Tamu diarahkan ke login dan siswa mendapat 403 untuk halaman laporan dan ekspor.
     */
    public function test_siswa_dilarang_akses_dan_tamu_diarahkan_ke_masuk_pada_semua_laporan(): void
    {
        $ruteLaporan = [
            route('admin.laporan.akses'),
            route('admin.laporan.akses.ekspor'),
            route('admin.laporan.nilai'),
            route('admin.laporan.nilai.ekspor'),
        ];

        foreach ($ruteLaporan as $url) {
            // Tamu (pastikan tidak ada sesi terautentikasi)
            auth()->logout();
            $this->get($url)->assertRedirect(route('masuk'));

            // Siswa (Forbidden 403)
            $this->actingAs($this->siswa1)->get($url)->assertForbidden();
        }
    }

    /**
     * 2. Riwayat akses hanya memuat log siswa (log admin tidak dimuat).
     */
    public function test_riwayat_akses_hanya_memuat_siswa_dan_admin_tidak_dicatat(): void
    {
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => now()->subHours(2),
            'aktivitas_terakhir' => now()->subHours(1),
            'durasi_detik' => 3600,
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.laporan.akses'));
        $res->assertOk();
        $res->assertSee('Ahmad Fauzi');
        $res->assertSee('XI MIPA 1');
        // Admin Irma ada di header nav bar, tetapi TIDAK BOLEH ada di isi data tabel riwayat akses
        $res->assertDontSee('<td class="px-4 py-3 font-bold text-ink">'."\n".'                                        Admin Irma', false);
    }

    /**
     * 3. Filter tanggal inklusif (sampai akhir hari 23:59:59 WIB) dan filter siswa.
     */
    public function test_filter_tanggal_inklusif_dan_filter_siswa(): void
    {
        Carbon::setTestNow('2026-10-11 12:00:00');

        // Log kemarin (10 Okt 2026 10:00)
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => '2026-10-10 10:00:00',
            'aktivitas_terakhir' => '2026-10-10 10:30:00',
            'durasi_detik' => 1800,
        ]);

        // Log hari ini malam (11 Okt 2026 22:00) -> harus masuk bila filter sampai 2026-10-11
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => '2026-10-11 22:00:00',
            'aktivitas_terakhir' => '2026-10-11 22:15:00',
            'durasi_detik' => 900,
        ]);

        // Log siswa 2
        LogAkses::create([
            'user_id' => $this->siswa2->id,
            'waktu_masuk' => '2026-10-11 08:00:00',
            'aktivitas_terakhir' => '2026-10-11 08:20:00',
            'durasi_detik' => 1200,
        ]);

        // Filter rentang 2026-10-10 s/d 2026-10-11 dengan siswa Ahmad Fauzi
        $res = $this->actingAs($this->admin)->get(route('admin.laporan.akses', [
            'tanggal_mulai' => '2026-10-10',
            'tanggal_selesai' => '2026-10-11',
            'cari' => 'ahmad_fauzi',
        ]));

        $res->assertOk();
        $res->assertSee('Ahmad Fauzi');
        $res->assertDontSee('Budi Pratama');
        // Total sesi Ahmad: 2
        $res->assertSee('Total Sesi');
    }

    /**
     * 4. Tab rekap per siswa memuat siswa yang belum pernah masuk dengan angka 0.
     */
    public function test_rekap_per_siswa_memuat_siswa_belum_pernah_masuk_dengan_angka_0(): void
    {
        // Siswa 1 ada log, Siswa 2 belum pernah masuk sama sekali
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => '2026-10-11 08:00:00',
            'aktivitas_terakhir' => '2026-10-11 08:30:00',
            'durasi_detik' => 1800,
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.laporan.akses', ['tab' => 'rekap']));
        $res->assertOk();
        $res->assertSee('Ahmad Fauzi');
        $res->assertSee('Budi Pratama');
        $res->assertSee('Belum pernah masuk');
    }

    /**
     * 5. Kolom nilai di laporan nilai web dinamis mengikuti bab terbit dan menampilkan status esai.
     */
    public function test_laporan_nilai_web_dinamis_mengikuti_bab_terbit_dan_nilai_tertinggi(): void
    {
        $pretest = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest']);
        $bab1 = Bab::create(['urutan' => 1, 'judul' => 'Bab 1 Sel', 'status_terbit' => true]);
        $kuisBab1 = Kuis::create(['bab_id' => $bab1->id, 'tipe_kuis' => 'kuis_bab', 'judul' => 'Kuis 1']);

        // Bab 2 Draf (TIDAK boleh muncul sebagai kolom)
        Bab::create(['urutan' => 2, 'judul' => 'Bab 2 Draf', 'status_terbit' => false]);

        $posttest = Kuis::create(['tipe_kuis' => 'posttest', 'judul' => 'Posttest']);

        // Percobaan 1 Ahmad: nilai 60
        PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 60.00,
            'nilai_akhir' => 60.00,
            'dibatalkan' => false,
            'esai_menunggu_nilai' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        // Percobaan 2 Ahmad: nilai 80 (nilai tertinggi 80 harus tampil)
        PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 80.00,
            'nilai_akhir' => 80.00,
            'dibatalkan' => false,
            'esai_menunggu_nilai' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        // Siswa 2 Budi: Kuis 1 ada esai belum dinilai
        PercobaanKuis::create([
            'kuis_id' => $kuisBab1->id,
            'user_id' => $this->siswa2->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 70.00,
            'nilai_akhir' => null,
            'dibatalkan' => false,
            'esai_menunggu_nilai' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.laporan.nilai'));
        $res->assertOk();
        $res->assertSee('Bab 1 Sel');
        $res->assertDontSee('Bab 2 Draf');
        $res->assertSee('80%'); // Nilai tertinggi Ahmad
        $res->assertSee('menunggu penilaian esai'); // Status esai Budi
        $res->assertSee('belum dikerjakan'); // Pretest & Posttest kosong
    }

    /**
     * 6. Percobaan yang dibatalkan tidak masuk rekap, tetapi muncul di sheet Detail dengan tanda.
     */
    public function test_percobaan_dibatalkan_tidak_masuk_rekap_tetapi_muncul_di_detail(): void
    {
        $kuis = Kuis::create(['tipe_kuis' => 'pretest', 'judul' => 'Pretest']);

        // Percobaan dibatalkan dengan nilai 95
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 95.00,
            'nilai_akhir' => 95.00,
            'dibatalkan' => true,
            'esai_menunggu_nilai' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        // Percobaan sah dengan nilai 70
        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'nilai_otomatis' => 70.00,
            'nilai_akhir' => 70.00,
            'dibatalkan' => false,
            'esai_menunggu_nilai' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.laporan.nilai'));
        $res->assertOk();
        // Rekap harus 70%, bukan 95%
        $res->assertSee('70%');
        $res->assertDontSee('95%');
    }

    /**
     * 7. Ekspor Excel Laporan Akses dibaca balik dengan PhpSpreadsheet:
     * memeriksa judul kolom, sheet "Riwayat Akses", sheet "Rekap per Siswa", waktu tanggal-waktu, dan durasi angka.
     */
    public function test_ekspor_excel_laporan_akses_format_benar(): void
    {
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => '2026-10-11 08:00:00',
            'aktivitas_terakhir' => '2026-10-11 08:45:00',
            'durasi_detik' => 2700,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.akses.ekspor'));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tempPath = tempnam(sys_get_temp_dir(), 'lms_akses_').'.xlsx';
        copy($response->getFile()->getPathname(), $tempPath);

        $spreadsheet = IOFactory::load($tempPath);
        $this->assertCount(2, $spreadsheet->getSheetNames());
        $this->assertEquals('Riwayat Akses', $spreadsheet->getSheet(0)->getTitle());
        $this->assertEquals('Rekap per Siswa', $spreadsheet->getSheet(1)->getTitle());

        // Cek sheet 1: Riwayat Akses
        $sheetRiwayat = $spreadsheet->getSheet(0);
        $this->assertEquals('Nama Lengkap', $sheetRiwayat->getCell('A1')->getValue());
        $this->assertEquals('Durasi (Detik)', $sheetRiwayat->getCell('E1')->getValue());
        $this->assertEquals('Ahmad Fauzi', $sheetRiwayat->getCell('A2')->getValue());
        $this->assertEquals(2700, $sheetRiwayat->getCell('E2')->getValue());
        $this->assertEquals(DataType::TYPE_NUMERIC, $sheetRiwayat->getCell('E2')->getDataType());
        $this->assertEquals('00:45:00', $sheetRiwayat->getCell('F2')->getValue());

        // Periksa sel waktu di Riwayat Akses adalah tipe numerik (Excel Date) dan isDateTime
        $cellWaktuMasuk = $sheetRiwayat->getCell('C2');
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellWaktuMasuk->getDataType());
        $this->assertTrue(Date::isDateTime($cellWaktuMasuk));

        // Cek sheet 2: Rekap per Siswa
        $sheetRekap = $spreadsheet->getSheet(1);
        $this->assertEquals('Jumlah Sesi', $sheetRekap->getCell('C1')->getValue());
        $this->assertEquals('Total Durasi (Detik)', $sheetRekap->getCell('D1')->getValue());
        $this->assertEquals(DataType::TYPE_NUMERIC, $sheetRekap->getCell('D2')->getDataType());
        $this->assertEquals('00:45:00', $sheetRekap->getCell('E2')->getValue());

        // Periksa sel akses pertama dan terakhir di Rekap per Siswa adalah isDateTime
        $cellAksesPertama = $sheetRekap->getCell('F2');
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellAksesPertama->getDataType());
        $this->assertTrue(Date::isDateTime($cellAksesPertama));

        unlink($tempPath);
    }

    /**
     * 8. Keamanan Formula Injection: AmanValueBinder berlaku di keempat sheet dan menetralkan =, +, -, @, tab, cr.
     */
    public function test_keamanan_formula_injection_pada_ekspor_excel(): void
    {
        $siswaJahat = User::factory()->create([
            'nama_lengkap' => '=HYPERLINK("http://attacker.com","Klik")',
            'username' => '+cmd|/C calc',
            'kelas' => '-1+1',
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        $siswaTab = User::factory()->create([
            'nama_lengkap' => "\tTabName",
            'username' => "\rCRUser",
            'kelas' => '@AtClass',
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        LogAkses::create([
            'user_id' => $siswaJahat->id,
            'waktu_masuk' => '2026-10-11 09:00:00',
            'aktivitas_terakhir' => '2026-10-11 09:10:00',
            'durasi_detik' => 600,
        ]);

        LogAkses::create([
            'user_id' => $siswaTab->id,
            'waktu_masuk' => '2026-10-11 10:00:00',
            'aktivitas_terakhir' => '2026-10-11 10:10:00',
            'durasi_detik' => 600,
        ]);

        $kuis = Kuis::create([
            'judul' => 'Kuis Uji Injeksi',
            'tipe_kuis' => 'pretest',
            'acak_soal' => false,
        ]);

        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswaJahat->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => 80.00,
            'nilai_akhir' => 80.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => '2026-10-11 09:00:00',
            'waktu_selesai' => '2026-10-11 09:10:00',
        ]);

        PercobaanKuis::create([
            'kuis_id' => $kuis->id,
            'user_id' => $siswaTab->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => 75.00,
            'nilai_akhir' => 75.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => '2026-10-11 10:00:00',
            'waktu_selesai' => '2026-10-11 10:10:00',
        ]);

        // Tes di file Laporan Akses (2 sheet)
        $resAkses = $this->actingAs($this->admin)->get(route('admin.laporan.akses.ekspor'));
        $tempAkses = tempnam(sys_get_temp_dir(), 'lms_sec_a_').'.xlsx';
        copy($resAkses->getFile()->getPathname(), $tempAkses);
        $spAkses = IOFactory::load($tempAkses);

        // Sheet 0: Riwayat Akses
        $shRiwayat = $spAkses->getSheet(0);
        for ($row = 2; $row <= $shRiwayat->getHighestRow(); $row++) {
            $valNama = $shRiwayat->getCell('A'.$row)->getValue();
            if (in_array($valNama, ['=HYPERLINK("http://attacker.com","Klik")', "\tTabName"], true)) {
                $this->assertEquals(DataType::TYPE_STRING, $shRiwayat->getCell('A'.$row)->getDataType());
                $this->assertFalse($shRiwayat->getCell('A'.$row)->isFormula());
            }
        }

        // Sheet 1: Rekap per Siswa
        $shRekapSiswa = $spAkses->getSheet(1);
        for ($row = 2; $row <= $shRekapSiswa->getHighestRow(); $row++) {
            $valNama = $shRekapSiswa->getCell('A'.$row)->getValue();
            if (in_array($valNama, ['=HYPERLINK("http://attacker.com","Klik")', "\tTabName"], true)) {
                $this->assertEquals(DataType::TYPE_STRING, $shRekapSiswa->getCell('A'.$row)->getDataType());
                $this->assertFalse($shRekapSiswa->getCell('A'.$row)->isFormula());
            }
        }
        unlink($tempAkses);

        // Tes di file Laporan Nilai (2 sheet)
        $resNilai = $this->actingAs($this->admin)->get(route('admin.laporan.nilai.ekspor'));
        $tempNilai = tempnam(sys_get_temp_dir(), 'lms_sec_n_').'.xlsx';
        copy($resNilai->getFile()->getPathname(), $tempNilai);
        $spNilai = IOFactory::load($tempNilai);

        // Sheet 0: Rekap Nilai
        $shRekapNilai = $spNilai->getSheet(0);
        for ($row = 2; $row <= $shRekapNilai->getHighestRow(); $row++) {
            $valNama = $shRekapNilai->getCell('A'.$row)->getValue();
            if (in_array($valNama, ['=HYPERLINK("http://attacker.com","Klik")', "\tTabName"], true)) {
                $this->assertEquals(DataType::TYPE_STRING, $shRekapNilai->getCell('A'.$row)->getDataType());
                $this->assertFalse($shRekapNilai->getCell('A'.$row)->isFormula());
            }
        }

        // Sheet 1: Detail Percobaan
        $shDetail = $spNilai->getSheet(1);
        for ($row = 2; $row <= $shDetail->getHighestRow(); $row++) {
            $valNama = $shDetail->getCell('B'.$row)->getValue();
            if (in_array($valNama, ['=HYPERLINK("http://attacker.com","Klik")', "\tTabName"], true)) {
                $this->assertEquals(DataType::TYPE_STRING, $shDetail->getCell('B'.$row)->getDataType());
                $this->assertFalse($shDetail->getCell('B'.$row)->isFormula());
            }
        }
        unlink($tempNilai);
    }

    /**
     * 9. Filter yang sama berlaku pada ekspor Excel nilai dan akses.
     */
    public function test_filter_berlaku_pada_ekspor_excel(): void
    {
        LogAkses::create([
            'user_id' => $this->siswa1->id,
            'waktu_masuk' => '2026-10-11 08:00:00',
            'aktivitas_terakhir' => '2026-10-11 08:30:00',
            'durasi_detik' => 1800,
        ]);

        LogAkses::create([
            'user_id' => $this->siswa2->id,
            'waktu_masuk' => '2026-10-11 09:00:00',
            'aktivitas_terakhir' => '2026-10-11 09:30:00',
            'durasi_detik' => 1800,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.akses.ekspor', [
            'cari' => 'ahmad_fauzi',
        ]));

        $tempPath = tempnam(sys_get_temp_dir(), 'lms_filt_').'.xlsx';
        copy($response->getFile()->getPathname(), $tempPath);

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getSheet(0);

        // Hanya memuat 1 baris data (baris 2) untuk Ahmad Fauzi
        $this->assertEquals('Ahmad Fauzi', $sheet->getCell('A2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue());

        unlink($tempPath);
    }

    /**
     * 10. Ekspor Laporan Nilai: Sheet Rekap Nilai dan Detail Percobaan dengan format tanggal dan pembatalan.
     */
    public function test_ekspor_excel_laporan_nilai_rekap_dan_detail_percobaan(): void
    {
        $bab = Bab::create([
            'judul' => 'Bab Ekspor Nilai',
            'urutan' => 1,
            'status_terbit' => true,
        ]);

        $kuisBab = Kuis::create([
            'bab_id' => $bab->id,
            'judul' => 'Kuis Bab Ekspor Nilai',
            'tipe_kuis' => 'kuis_bab',
            'nilai_lulus' => 65.00,
            'maksimal_kesempatan' => 2,
            'acak_soal' => false,
        ]);

        // Percobaan 1 Ahmad: 90 tapi dibatalkan
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => 90.00,
            'nilai_akhir' => 90.00,
            'dibatalkan' => true,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => '2026-10-11 08:00:00',
            'waktu_selesai' => '2026-10-11 08:20:00',
        ]);

        // Percobaan 2 Ahmad: 75 aktif
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa1->id,
            'nomor_percobaan' => 2,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'esai_menunggu_nilai' => false,
            'nilai_otomatis' => 75.00,
            'nilai_akhir' => 75.00,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => '2026-10-11 09:00:00',
            'waktu_selesai' => '2026-10-11 09:20:00',
        ]);

        // Percobaan 1 Budi: esai belum dinilai
        PercobaanKuis::create([
            'kuis_id' => $kuisBab->id,
            'user_id' => $this->siswa2->id,
            'nomor_percobaan' => 1,
            'status_pengerjaan' => 'selesai',
            'status_kelulusan' => 'lulus',
            'esai_menunggu_nilai' => true,
            'nilai_otomatis' => 70.00,
            'nilai_akhir' => null,
            'dibatalkan' => false,
            'urutan_soal_teracak' => [],
            'waktu_mulai' => '2026-10-11 10:00:00',
            'waktu_selesai' => '2026-10-11 10:20:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.nilai.ekspor'));
        $tempPath = tempnam(sys_get_temp_dir(), 'lms_nilai_').'.xlsx';
        copy($response->getFile()->getPathname(), $tempPath);

        $spreadsheet = IOFactory::load($tempPath);

        // Sheet 0: Rekap Nilai
        $shRekap = $spreadsheet->getSheet(0);
        $this->assertEquals('Rekap Nilai', $shRekap->getTitle());
        // Nilai Ahmad harus 75 (angka numerik), bukan 90 karena 90 dibatalkan
        $valAhmad = $shRekap->getCell('E2')->getValue(); // Kolom E adalah Bab Ekspor Nilai (Nilai)
        $this->assertEquals(75, $valAhmad);
        $this->assertEquals(DataType::TYPE_NUMERIC, $shRekap->getCell('E2')->getDataType());

        // Status Budi: "menunggu penilaian esai"
        $valBudiStatus = $shRekap->getCell('F3')->getValue(); // Status Bab Ekspor Nilai
        $this->assertEquals('menunggu penilaian esai', $valBudiStatus);

        // Sheet 1: Detail Percobaan
        $shDetail = $spreadsheet->getSheet(1);
        $this->assertEquals('Detail Percobaan', $shDetail->getTitle());

        // Periksa sel waktu mulai di Detail Percobaan bertipe numerik dan isDateTime
        $cellWaktuMulai = $shDetail->getCell('L2');
        $this->assertEquals(DataType::TYPE_NUMERIC, $cellWaktuMulai->getDataType());
        $this->assertTrue(Date::isDateTime($cellWaktuMulai));

        // Periksa tanda Dibatalkan Ya dan Tidak
        $dibatalkanValues = [];
        for ($r = 2; $r <= $shDetail->getHighestRow(); $r++) {
            $dibatalkanValues[] = $shDetail->getCell('N'.$r)->getValue();
        }
        $this->assertContains('Ya', $dibatalkanValues);
        $this->assertContains('Tidak', $dibatalkanValues);

        unlink($tempPath);
    }
}
