<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Jalankan seeder akun admin awal (Irma).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama_lengkap' => 'Irma (Admin)',
                'email' => 'admin@lms-sekolah.test',
                'password' => Hash::make('password123'),
                'peran' => 'admin',
                'kelas' => null,
                'aktif' => true,
            ]
        );
    }
}
