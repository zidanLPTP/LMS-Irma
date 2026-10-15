<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Jalankan seeder akun admin awal (Irma).
     * Membaca kredensial dari environment (.env).
     */
    public function run(): void
    {
        $nama = env('ADMIN_NAMA', 'Irma Pengelola');
        $username = env('ADMIN_USERNAME', 'admin');
        $email = env('ADMIN_EMAIL', 'admin@biostemx.test');
        $password = env('ADMIN_PASSWORD', 'password123');

        if (app()->environment('production') || config('app.env') === 'production') {
            if (empty($nama) || empty($username) || empty($email) || empty($password)) {
                throw new \RuntimeException('Kredensial admin produksi tidak boleh kosong di .env.');
            }

            if (strlen($password) < 12) {
                throw new \RuntimeException('Kata sandi admin produksi minimal 12 karakter.');
            }
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'nama_lengkap' => $nama,
                'email' => $email,
                'password' => Hash::make($password),
                'peran' => 'admin',
                'kelas' => null,
                'aktif' => true,
                'wajib_ganti_password' => app()->environment('production') ? true : false,
            ]
        );
    }
}
