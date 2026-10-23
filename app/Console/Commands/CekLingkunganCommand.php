<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CekLingkunganCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah konsol.
     */
    protected $signature = 'lms:cek-lingkungan';

    /**
     * Deskripsi perintah konsol.
     */
    protected $description = 'Memeriksa kesiapan lingkungan hosting cPanel/SSH untuk deployment produksi';

    /**
     * Eksekusi perintah konsol.
     */
    public function handle(): int
    {
        $this->info('=== Pemeriksaan Kesiapan Lingkungan Hosting BioSTEMX ===');
        $this->newLine();

        $hasil = [];
        $semuaLulus = true;

        // 1. Versi PHP
        $versiPhp = phpversion();
        $versiValid = version_compare($versiPhp, '8.2.0', '>=');
        $hasil[] = [
            'Komponen' => 'Versi PHP (>= 8.2)',
            'Nilai Terdeteksi' => $versiPhp,
            'Status' => $versiValid ? 'LULUS' : 'GAGAL',
        ];
        if (! $versiValid) {
            $semuaLulus = false;
        }

        // 2. Ekstensi PHP
        $daftarEkstensi = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        foreach ($daftarEkstensi as $ext) {
            $terpasang = extension_loaded($ext);
            $hasil[] = [
                'Komponen' => "Ekstensi PHP: {$ext}",
                'Nilai Terdeteksi' => $terpasang ? 'Tersedia' : 'Tidak Ada',
                'Status' => $terpasang ? 'LULUS' : 'GAGAL',
            ];
            if (! $terpasang) {
                $semuaLulus = false;
            }
        }

        // 3. Izin Tulis Direktori
        $daftarFolder = [
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        foreach ($daftarFolder as $namaFolder => $path) {
            $bisaDitulis = is_dir($path) && is_writable($path);
            $hasil[] = [
                'Komponen' => "Izin Tulis: {$namaFolder}",
                'Nilai Terdeteksi' => $bisaDitulis ? 'Bisa Ditulis (Writable)' : 'Terkunci',
                'Status' => $bisaDitulis ? 'LULUS' : 'GAGAL',
            ];
            if (! $bisaDitulis) {
                $semuaLulus = false;
            }
        }

        // 4. Koneksi Database
        $dbStatus = 'Terhubung';
        $dbLulus = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'Galat: '.$e->getMessage();
            $dbLulus = false;
            $semuaLulus = false;
        }
        $hasil[] = [
            'Komponen' => 'Koneksi Database',
            'Nilai Terdeteksi' => $dbStatus,
            'Status' => $dbLulus ? 'LULUS' : 'GAGAL',
        ];

        // 5. Driver Sesi & Cache (Hindari Redis di Shared Hosting)
        $driverSesi = config('session.driver');
        $driverCache = config('cache.default');
        $driverAman = in_array($driverSesi, ['file', 'database', 'cookie']) && in_array($driverCache, ['file', 'database', 'array']);
        $hasil[] = [
            'Komponen' => 'Driver Sesi & Cache (Tanpa Redis)',
            'Nilai Terdeteksi' => "Sesi: {$driverSesi}, Cache: {$driverCache}",
            'Status' => $driverAman ? 'LULUS' : 'PERINGATAN',
        ];

        // 6. Manifest Aset Vite
        $manifestPath = public_path('build/manifest.json');
        $manifestAda = file_exists($manifestPath);
        $hasil[] = [
            'Komponen' => 'Aset Frontend (public/build)',
            'Nilai Terdeteksi' => $manifestAda ? 'Tersedia (manifest.json terdeteksi)' : 'Belum Ada',
            'Status' => $manifestAda ? 'LULUS' : 'PERINGATAN',
        ];

        $this->table(['Pemeriksaan Komponen', 'Nilai Terdeteksi', 'Status'], $hasil);

        $this->newLine();
        if ($semuaLulus) {
            $this->info('✓ Lingkungan server memenuhi seluruh prasyarat hosting BioSTEMX.');

            return self::SUCCESS;
        }

        $this->warn('! Terdapat komponen yang belum memenuhi kriteria. Periksa tabel di atas.');

        return self::FAILURE;
    }
}
