<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CetakSqlAdminCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     */
    protected $signature = 'lms:cetak-sql-admin
                            {--nama=Irma Admin : Nama lengkap admin}
                            {--username=admin : Username login}
                            {--email=admin@sekolah.sch.id : Alamat surel}
                            {--password=AdminBioSTEMX2026! : Kata sandi}';

    /**
     * Deskripsi perintah konsol.
     */
    protected $description = 'Mencetak kueri SQL INSERT akun admin untuk jalur cadangan via phpMyAdmin';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(): int
    {
        $nama = addslashes($this->option('nama'));
        $username = addslashes($this->option('username'));
        $email = addslashes($this->option('email'));
        $password = $this->option('password') ?: 'AdminBioSTEMX2026!';

        // Menggunakan Bcrypt cost 10 standar
        $hashPassword = Hash::make($password, ['rounds' => 10]);

        $sql = "INSERT INTO `users` (`nama_lengkap`, `username`, `email`, `password`, `peran`, `kelas`, `aktif`, `wajib_ganti_password`, `created_at`, `updated_at`) VALUES ('{$nama}', '{$username}', '{$email}', '{$hashPassword}', 'admin', NULL, 1, 1, NOW(), NOW());";

        $this->info('-- Kueri SQL Pembuatan Akun Admin (Salin dan jalankan di tab SQL phpMyAdmin):');
        $this->line($sql);
        $this->newLine();
        $this->comment("Catatan kredensial: Username: {$username} | Kata Sandi: {$password}");

        return self::SUCCESS;
    }
}
