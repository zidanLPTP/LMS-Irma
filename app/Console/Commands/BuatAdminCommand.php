<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BuatAdminCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     */
    protected $signature = 'lms:buat-admin
                            {--nama= : Nama lengkap admin}
                            {--username= : Nama pengguna unik untuk login}
                            {--email= : Alamat surel admin}
                            {--password= : Kata sandi (minimal 12 karakter)}';

    /**
     * Deskripsi perintah konsol.
     */
    protected $description = 'Membuat akun administrator baru di lingkungan hosting produksi dengan kata sandi tersembunyi';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(): int
    {
        $this->info('=== Pembuatan Akun Administrator Produksi BioSTEMX ===');

        $nama = $this->option('nama') ?: $this->ask('Nama Lengkap Admin', 'Administrator BioSTEMX');
        $username = $this->option('username') ?: $this->ask('Username Admin');
        $email = $this->option('email') ?: $this->ask('Email Admin');

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Kata Sandi Admin (minimal 12 karakter)');
            $konfirmasi = $this->secret('Ulangi Kata Sandi');

            if ($password !== $konfirmasi) {
                $this->error('Kata sandi dan konfirmasi kata sandi tidak cocok.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make([
            'nama_lengkap' => $nama,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ], [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ], [
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'password.min' => 'Kata sandi minimal 12 karakter untuk lingkungan produksi.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesanGalat) {
                $this->error('Galat: '.$pesanGalat);
            }

            return self::FAILURE;
        }

        $admin = User::create([
            'nama_lengkap' => $nama,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'peran' => 'admin',
            'kelas' => null,
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        $this->newLine();
        $this->info('✓ Admin berhasil dibuat!');
        $this->table(['ID', 'Nama Lengkap', 'Username', 'Email', 'Peran', 'Wajib Ganti Sandi'], [
            [$admin->id, $admin->nama_lengkap, $admin->username, $admin->email, $admin->peran, 'Ya (Login Pertama)'],
        ]);

        $this->comment('Catatan: Akun diwajibkan mengganti kata sandi saat pertama kali masuk ke sistem.');

        return self::SUCCESS;
    }
}
