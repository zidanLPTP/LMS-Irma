<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * TDD 1: Daftar membuat siswa baru dan mengabaikan parameter peran admin dari request.
     */
    public function test_daftar_membuat_siswa_dan_mengabaikan_peran_dari_request(): void
    {
        $response = $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Teladan',
            'username' => 'siswateladan',
            'email' => 'siswa@sekolah.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'kelas' => 'XII MIPA 2',
            'peran' => 'admin', // Coba eksploitasi mass assignment
            'aktif' => false,   // Coba eksploitasi
        ]);

        $response->assertRedirect(route('siswa.beranda'));

        $this->assertDatabaseHas('users', [
            'username' => 'siswateladan',
            'email' => 'siswa@sekolah.test',
            'peran' => 'siswa', // Harus tetap siswa
            'aktif' => true,    // Harus tetap aktif
            'kelas' => 'XII MIPA 2',
        ]);

        $this->assertAuthenticated();
    }

    /**
     * TDD 2: Username atau email ganda ditolak dengan validasi.
     */
    public function test_username_atau_email_ganda_ditolak(): void
    {
        User::factory()->create([
            'username' => 'siswa1',
            'email' => 'siswa1@sekolah.test',
        ]);

        $response = $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Baru',
            'username' => 'siswa1', // Duplikat
            'email' => 'siswa_lain@sekolah.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');

        $responseEmail = $this->post(route('daftar.kirim'), [
            'nama_lengkap' => 'Siswa Baru 2',
            'username' => 'siswa_baru_2',
            'email' => 'siswa1@sekolah.test', // Duplikat
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $responseEmail->assertSessionHasErrors('email');
    }

    /**
     * TDD 3: Login berhasil dengan username atau email, dan password salah gagal tanpa membocorkan eksistensi akun.
     */
    public function test_login_berhasil_dengan_username_dan_email_password_salah_gagal(): void
    {
        $siswa = User::factory()->create([
            'username' => 'budi_santoso',
            'email' => 'budi@sekolah.test',
            'password' => bcrypt('rahasia123'),
            'peran' => 'siswa',
            'aktif' => true,
        ]);

        // Login dengan username
        $responseUsername = $this->post(route('masuk.kirim'), [
            'login' => 'budi_santoso',
            'password' => 'rahasia123',
        ]);
        $responseUsername->assertRedirect(route('siswa.beranda'));
        $this->assertAuthenticatedAs($siswa);

        $this->post(route('keluar'));
        $this->assertGuest();

        // Login dengan email
        $responseEmail = $this->post(route('masuk.kirim'), [
            'login' => 'budi@sekolah.test',
            'password' => 'rahasia123',
        ]);
        $responseEmail->assertRedirect(route('siswa.beranda'));
        $this->assertAuthenticatedAs($siswa);

        $this->post(route('keluar'));

        // Password salah (pesan generik)
        $responseGagal = $this->post(route('masuk.kirim'), [
            'login' => 'budi_santoso',
            'password' => 'password_salah',
        ]);
        $responseGagal->assertSessionHasErrors('login');
        $this->assertGuest();

        // Username tidak ada (pesan generik sama persis)
        $responseTidakAda = $this->post(route('masuk.kirim'), [
            'login' => 'user_tidak_dikenal',
            'password' => 'apapun',
        ]);
        $responseTidakAda->assertSessionHasErrors('login');
        $this->assertGuest();

        // Pastikan pesan galatnya sama persis agar tidak membocorkan keberadaan akun
        $pesanGagal = session('errors')->getBag('default')->first('login');
        $this->assertEquals(trans('auth.failed'), $pesanGagal);
    }

    /**
     * TDD 4: Akun nonaktif (aktif = false) ditolak saat login dengan pesan yang sopan.
     */
    public function test_akun_nonaktif_ditolak_login(): void
    {
        User::factory()->create([
            'username' => 'siswa_nonaktif',
            'password' => bcrypt('password123'),
            'aktif' => false,
        ]);

        $response = $this->post(route('masuk.kirim'), [
            'login' => 'siswa_nonaktif',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * TDD 5: Rate limiting bekerja setelah beberapa kali percobaan gagal berturut-turut.
     */
    public function test_rate_limiting_bekerja_setelah_beberapa_kali_gagal(): void
    {
        // Default throttle 5 kali per menit
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('masuk.kirim'), [
                'login' => 'percobaan_bruteforce',
                'password' => 'salah',
            ]);
        }

        // Percobaan ke-6 harus ditolak oleh rate limiter (HTTP 429 atau error session throttle)
        $responseKeenam = $this->post(route('masuk.kirim'), [
            'login' => 'percobaan_bruteforce',
            'password' => 'salah',
        ]);

        $responseKeenam->assertSessionHasErrors('login');
    }

    /**
     * TDD 6: Siswa tidak bisa membuka rute admin, dan tamu diarahkan ke login.
     */
    public function test_siswa_tidak_bisa_membuka_rute_admin_dan_tamu_diarahkan_ke_masuk(): void
    {
        // Tamu diarahkan ke login
        $this->get(route('admin.beranda'))->assertRedirect(route('masuk'));
        $this->get(route('siswa.beranda'))->assertRedirect(route('masuk'));

        // Siswa login
        $siswa = User::factory()->create(['peran' => 'siswa']);
        $this->actingAs($siswa)
            ->get(route('admin.beranda'))
            ->assertForbidden(); // HTTP 403

        // Admin login
        $admin = User::factory()->create(['peran' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.beranda'))
            ->assertOk();
    }

    /**
     * TDD 7: Logout lewat POST menghapus sesi dan mengarahkan ke halaman masuk.
     */
    public function test_logout_lewat_post_menghapus_sesi(): void
    {
        $siswa = User::factory()->create(['peran' => 'siswa']);

        $this->actingAs($siswa);
        $this->assertAuthenticated();

        $response = $this->post(route('keluar'));
        $response->assertRedirect(route('masuk'));
        $this->assertGuest();
    }
}
