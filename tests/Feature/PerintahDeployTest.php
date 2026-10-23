<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerintahDeployTest extends TestCase
{
    use RefreshDatabase;

    public function test_perintah_buat_admin_berhasil_membuat_admin_baru_dengan_wajib_ganti_password(): void
    {
        $this->artisan('lms:buat-admin', [
            '--nama' => 'Irma Pengelola Baru',
            '--username' => 'admin_irma_baru',
            '--email' => 'irma_baru@biostemx.test',
            '--password' => 'AdminKuat2026!Baru',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Admin berhasil dibuat!');

        $admin = User::where('username', 'admin_irma_baru')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('Irma Pengelola Baru', $admin->nama_lengkap);
        $this->assertEquals('irma_baru@biostemx.test', $admin->email);
        $this->assertEquals('admin', $admin->peran);
        $this->assertTrue($admin->aktif);
        $this->assertTrue($admin->wajib_ganti_password);
        $this->assertTrue(Hash::check('AdminKuat2026!Baru', $admin->password));
    }

    public function test_perintah_buat_admin_menolak_password_kurang_dari_12_karakter(): void
    {
        $this->artisan('lms:buat-admin', [
            '--nama' => 'Irma Pendek',
            '--username' => 'admin_pendek',
            '--email' => 'pendek@biostemx.test',
            '--password' => 'pendek123',
        ])
            ->assertFailed()
            ->expectsOutputToContain('minimal 12 karakter');

        $this->assertDatabaseMissing('users', ['username' => 'admin_pendek']);
    }

    public function test_perintah_buat_admin_menolak_username_ganda(): void
    {
        User::factory()->create([
            'username' => 'admin_sama',
            'email' => 'pertama@biostemx.test',
        ]);

        $this->artisan('lms:buat-admin', [
            '--nama' => 'Admin Duplikat',
            '--username' => 'admin_sama',
            '--email' => 'kedua@biostemx.test',
            '--password' => 'AdminKuat2026!Baru',
        ])
            ->assertFailed()
            ->expectsOutputToContain('Username sudah digunakan');
    }

    public function test_perintah_cek_lingkungan_berjalan_dan_menampilkan_pemeriksaan(): void
    {
        $this->artisan('lms:cek-lingkungan')
            ->assertSuccessful()
            ->expectsOutputToContain('Pemeriksaan Kesiapan Lingkungan Hosting');
    }

    public function test_perintah_cetak_sql_admin_menghasilkan_kueri_insert_valid(): void
    {
        $this->artisan('lms:cetak-sql-admin', [
            '--nama' => 'Admin Cetak',
            '--username' => 'admin_cetak',
            '--email' => 'cetak@biostemx.test',
            '--password' => 'AdminKuat2026!SQL',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('INSERT INTO `users`');
    }
}
