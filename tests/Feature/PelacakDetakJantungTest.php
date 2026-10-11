<?php

namespace Tests\Feature;

use App\Models\LogAkses;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PelacakDetakJantungTest extends TestCase
{
    use RefreshDatabase;

    protected User $siswa;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siswa = User::factory()->create([
            'nama_lengkap' => 'Siswa Riset',
            'username' => 'siswa_riset',
            'email' => 'riset@siswa.test',
            'password' => bcrypt('password123'),
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        $this->admin = User::factory()->create([
            'nama_lengkap' => 'Admin Irma',
            'username' => 'admin_irma',
            'email' => 'irma@admin.test',
            'password' => bcrypt('password123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);
    }

    /**
     * 1. Siswa mendapat status 204 dan durasi bertambah sesuai jeda (dalam batas toleransi).
     */
    public function test_siswa_mendapat_204_dan_durasi_bertambah_sesuai_jeda(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        $logAwal = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();
        $this->assertNotNull($logAwal);
        $this->assertEquals(0, $logAwal->durasi_detik);

        // Majukan waktu 45 detik
        Carbon::setTestNow('2026-10-11 08:00:45');

        $response = $this->post(route('siswa.detak'), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertNoContent(); // HTTP 204

        $logAwal->refresh();
        $this->assertEquals(45, $logAwal->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:45', $logAwal->aktivitas_terakhir->toDateTimeString());
    }

    /**
     * 2. Jeda lebih dari 120 detik membuat baris log baru dengan durasi 0.
     */
    public function test_jeda_lebih_dari_120_detik_membuat_baris_log_baru(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        $log1 = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();

        // Majukan 150 detik (melewati batas 120 detik)
        Carbon::setTestNow('2026-10-11 08:02:30');

        $response = $this->post(route('siswa.detak'), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertNoContent();

        $log1->refresh();
        $this->assertEquals(0, $log1->durasi_detik); // Log lama tidak ditambah

        $log2 = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();
        $this->assertNotEquals($log1->id, $log2->id);
        $this->assertEquals(0, $log2->durasi_detik);
        $this->assertEquals('2026-10-11 08:02:30', $log2->waktu_masuk->toDateTimeString());
    }

    /**
     * 3. Admin mendapat status 204 tanpa membuat baris log_akses.
     */
    public function test_admin_mendapat_204_tanpa_mencatat_log_akses(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('siswa.detak'), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('log_akses', [
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * 4. Tamu mendapat 401 bila Accept: application/json.
     */
    public function test_tamu_mendapat_401_dengan_accept_json(): void
    {
        $response = $this->post(route('siswa.detak'), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 5. Permintaan tanpa token CSRF yang sah ditolak (419 dengan Accept: application/json).
     */
    public function test_post_tanpa_token_csrf_ditolak_419(): void
    {
        $this->actingAs($this->siswa);
        $this->get('/'); // Sesi

        $req = Request::create(route('siswa.detak'), 'POST');
        $req->headers->set('Accept', 'application/json');
        $req->setLaravelSession(session()->driver());

        $handler = app(ExceptionHandler::class);
        $response = $handler->render($req, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertEquals(419, $response->getStatusCode());
    }

    /**
     * 6. Throttle menghasilkan 429 setelah melebihi batas 10 per menit.
     */
    public function test_throttle_menghasilkan_429_setelah_10_permintaan_per_menit(): void
    {
        $this->actingAs($this->siswa);

        for ($i = 0; $i < 10; $i++) {
            $res = $this->post(route('siswa.detak'), [], ['Accept' => 'application/json']);
            $res->assertNoContent();
        }

        // Permintaan ke-11 harus 429 Too Many Requests
        $resKe11 = $this->post(route('siswa.detak'), [], ['Accept' => 'application/json']);
        $resKe11->assertStatus(429);
    }

    /**
     * 7. Body berisi durasi atau waktu palsu diabaikan (server menghitung sendiri).
     */
    public function test_body_berisi_durasi_atau_waktu_palsu_diabaikan(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        Carbon::setTestNow('2026-10-11 08:00:30');

        // Klien iseng mengirim durasi_detik: 999999 atau waktu_akses palsu
        $response = $this->post(route('siswa.detak'), [
            'durasi_detik' => 999999,
            'waktu_masuk' => '2020-01-01 00:00:00',
            'aktivitas_terakhir' => '2099-01-01 00:00:00',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertNoContent();

        $log = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();
        // Durasi harus tetap dihitung server (30 detik), bukan 999999
        $this->assertEquals(30, $log->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:30', $log->aktivitas_terakhir->toDateTimeString());
    }

    /**
     * 8. Panggilan kurang dari 5 detik tidak menambah durasi dan tidak menggeser aktivitas_terakhir.
     */
    public function test_panggilan_kurang_dari_5_detik_tidak_menambah_durasi_dan_tidak_menggeser_aktivitas(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        $log = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();

        // Majukan hanya 3 detik
        Carbon::setTestNow('2026-10-11 08:00:03');

        $res = $this->post(route('siswa.detak'), [], ['Accept' => 'application/json']);
        $res->assertNoContent();

        $log->refresh();
        // Durasi tetap 0 dan aktivitas_terakhir tetap waktu masuk
        $this->assertEquals(0, $log->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:00', $log->aktivitas_terakhir->toDateTimeString());

        // Setelah 10 detik lagi (total 13 detik dari awal)
        Carbon::setTestNow('2026-10-11 08:00:13');
        $res2 = $this->post(route('siswa.detak'), [], ['Accept' => 'application/json']);
        $res2->assertNoContent();

        $log->refresh();
        // Selisih 13 detik terhitung penuh karena aktivitas_terakhir sebelumnya tidak bergeser
        $this->assertEquals(13, $log->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:13', $log->aktivitas_terakhir->toDateTimeString());
    }

    /**
     * 9. GET beranda siswa memperbarui aktivitas_terakhir lewat middleware CatatAktivitas.
     */
    public function test_get_beranda_siswa_memperbarui_aktivitas_lewat_middleware(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        $log = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();

        // 20 detik kemudian siswa membuka beranda (GET)
        Carbon::setTestNow('2026-10-11 08:00:20');
        $res = $this->get(route('siswa.beranda'));
        $res->assertOk();

        $log->refresh();
        $this->assertEquals(20, $log->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:20', $log->aktivitas_terakhir->toDateTimeString());
    }

    /**
     * 10. Sesi tanpa id_log_akses_aktif (misal sesi lama / hilang) membuat baris log baru.
     */
    public function test_sesi_tanpa_id_log_akses_aktif_membuat_baris_log_baru(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->actingAs($this->siswa);
        session()->forget('id_log_akses_aktif');

        $res = $this->post(route('siswa.detak'), [], ['Accept' => 'application/json']);
        $res->assertNoContent();

        $log = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('2026-10-11 08:00:00', $log->waktu_masuk->toDateTimeString());
    }

    /**
     * 11. Keluar (logout) menutup sesi dan menambah jeda terakhir yang valid.
     */
    public function test_keluar_menutup_sesi_dan_menambah_jeda_terakhir_yang_valid(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        $this->post(route('masuk.kirim'), [
            'login' => $this->siswa->username,
            'password' => 'password123',
        ]);

        $log = LogAkses::where('user_id', $this->siswa->id)->latest('id')->first();

        // 50 detik kemudian siswa menekan tombol Keluar
        Carbon::setTestNow('2026-10-11 08:00:50');
        $res = $this->post(route('keluar'));
        $res->assertRedirect(route('masuk'));

        $log->refresh();
        $this->assertEquals(50, $log->durasi_detik);
        $this->assertEquals('2026-10-11 08:00:50', $log->aktivitas_terakhir->toDateTimeString());
    }
}
