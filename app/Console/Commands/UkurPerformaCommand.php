<?php

namespace App\Console\Commands;

use App\Exports\Sheets\RekapNilaiSheet;
use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\LaporanNilaiController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Siswa\BerandaController as SiswaBerandaController;
use App\Http\Controllers\Siswa\DetakJantungController;
use App\Models\User;
use App\Services\BukaAksesService;
use App\Services\PelacakAksesService;
use App\Services\PenilaianKuisService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UkurPerformaCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     *
     * @var string
     */
    protected $signature = 'lms:ukur-performa';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Ukur performa halaman dan endpoint LMS (waktu, query SQL via DB::listen, dan memori)';

    /**
     * Eksekusi perintah console.
     */
    public function handle(
        BukaAksesService $bukaAksesService,
        PenilaianKuisService $penilaianService,
        PelacakAksesService $pelacakAksesService
    ): int {
        if (App::environment('production')) {
            $this->error('Perintah lms:ukur-performa DILARANG dijalankan di lingkungan produksi!');

            return self::FAILURE;
        }

        $this->info('Memulai pengukuran performa LMS (BioSTEMX)...');

        $admin = User::where('peran', 'admin')->first();
        $siswa = User::where('peran', 'siswa')->first();

        if (! $admin || ! $siswa) {
            $this->error('Pengguna admin atau siswa belum tersedia. Jalankan database seeder terlebih dahulu.');

            return self::FAILURE;
        }

        $hasil = [];

        // 1. Dasbor Admin (/admin/beranda)
        $hasil[] = $this->ukurAksi('Dasbor Admin (/admin/beranda)', function () use ($admin, $bukaAksesService) {
            Auth::login($admin);
            $controller = app(AdminBerandaController::class);
            $view = $controller->index($bukaAksesService);
            $view->render();
        });

        // 2. Daftar Pengguna (/admin/pengguna - 25 baris)
        $hasil[] = $this->ukurAksi('Daftar Pengguna (/admin/pengguna)', function () use ($admin) {
            Auth::login($admin);
            $controller = app(PenggunaController::class);
            $req = Request::create('/admin/pengguna', 'GET');
            $req->setUserResolver(fn () => $admin);
            $view = $controller->index($req);
            $view->render();
        });

        // 3. Beranda Siswa (/siswa/beranda)
        $hasil[] = $this->ukurAksi('Beranda Siswa (/siswa/beranda)', function () use ($siswa) {
            Auth::login($siswa);
            $controller = app(SiswaBerandaController::class);
            $view = $controller->index();
            $view->render();
        });

        // 4. Laporan Nilai Web (/admin/laporan/nilai)
        $hasil[] = $this->ukurAksi('Laporan Nilai Siswa (/admin/laporan/nilai)', function () use ($admin, $penilaianService) {
            Auth::login($admin);
            $controller = app(LaporanNilaiController::class);
            $req = Request::create('/admin/laporan/nilai', 'GET');
            $req->setUserResolver(fn () => $admin);
            $view = $controller->index($req, $penilaianService);
            $view->render();
        });

        // 5. Ekspor Excel Rekap Nilai (RekapNilaiSheet)
        $hasil[] = $this->ukurAksi('Ekspor Excel Rekap Nilai (RekapNilaiSheet)', function () {
            $sheet = new RekapNilaiSheet;
            $data = $sheet->query()->get();
            foreach ($data as $baris) {
                $sheet->map($baris);
            }
        });

        // 6. Endpoint Detak (Heartbeat /detak)
        $hasil[] = $this->ukurAksi('Endpoint Detak (Heartbeat /detak)', function () use ($siswa, $pelacakAksesService) {
            Auth::login($siswa);
            $controller = app(DetakJantungController::class);
            $req = Request::create('/detak', 'POST');
            $req->setUserResolver(fn () => $siswa);
            $controller($req, $pelacakAksesService);
        });

        $this->table(
            ['Fitur / Halaman', 'Waktu (ms)', 'Jumlah Query SQL', 'Memori Puncak (MB)'],
            $hasil
        );

        return self::SUCCESS;
    }

    /**
     * Helper untuk mengukur eksekusi aksi dengan DB::listen dan memory_get_peak_usage.
     */
    protected function ukurAksi(string $nama, callable $callback): array
    {
        $jumlahQuery = 0;
        $listener = function () use (&$jumlahQuery) {
            $jumlahQuery++;
        };

        DB::listen($listener);

        $memoriAwal = memory_get_peak_usage(true);
        $waktuAwal = microtime(true);

        $callback();

        $waktuSelesai = microtime(true);
        $memoriPuncak = memory_get_peak_usage(true);

        // Hentikan listener dengan mengambil semua listener dan meresetnya
        // atau memanfaatkan event dispatcher
        app('events')->forget('Illuminate\Database\Events\QueryExecuted');

        $durasiMs = round(($waktuSelesai - $waktuAwal) * 1000, 2);
        $memoriMb = round($memoriPuncak / (1024 * 1024), 2);

        return [
            $nama,
            number_format($durasiMs, 2, ',', '.').' ms',
            $jumlahQuery,
            number_format($memoriMb, 2, ',', '.').' MB',
        ];
    }
}
