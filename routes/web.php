<?php

use App\Http\Controllers\Admin\BabController;
use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\KuisController;
use App\Http\Controllers\Admin\LaporanAksesController;
use App\Http\Controllers\Admin\LaporanNilaiController;
use App\Http\Controllers\Admin\MateriController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PenilaianEsaiController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\MasukController;
use App\Http\Controllers\PenyajiMateriPdfController;
use App\Http\Controllers\Siswa\BerandaController as SiswaBerandaController;
use App\Http\Controllers\Siswa\DetakJantungController;
use App\Http\Controllers\Siswa\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Bahasa Indonesia sesuai AGENTS.md)
|--------------------------------------------------------------------------
*/

// Halaman utama diarahkan ke masuk
Route::get('/', function () {
    return redirect()->route('masuk');
});

// Autentikasi Tamu (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [MasukController::class, 'create'])->name('masuk');
    Route::post('/masuk', [MasukController::class, 'store'])->name('masuk.kirim');

    Route::get('/daftar', [DaftarController::class, 'create'])->name('daftar');
    Route::post('/daftar', [DaftarController::class, 'store'])->name('daftar.kirim');
});

// Keluar (Harus Login)
Route::post('/keluar', [MasukController::class, 'destroy'])->middleware('auth')->name('keluar');

// Area Siswa
Route::middleware(['auth', 'peran:siswa', 'periksa.status', 'catat.aktivitas'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/beranda', [SiswaBerandaController::class, 'index'])->name('beranda');
    Route::get('/bab/{bab}', [App\Http\Controllers\Siswa\BabController::class, 'show'])->name('bab.show');
    Route::get('/bab/{bab}/materi/{materi}', [App\Http\Controllers\Siswa\MateriController::class, 'show'])->name('bab.materi.show');

    // Engine Kuis Siswa
    Route::get('/kuis/{kuis}', [App\Http\Controllers\Siswa\KuisController::class, 'show'])->name('kuis.show');
    Route::post('/kuis/{kuis}/mulai', [App\Http\Controllers\Siswa\KuisController::class, 'mulai'])->name('kuis.mulai');
    Route::get('/percobaan/{percobaan}/soal', [App\Http\Controllers\Siswa\KuisController::class, 'soal'])->name('percobaan.soal');
    Route::post('/percobaan/{percobaan}/soal/{soal}/jawab', [App\Http\Controllers\Siswa\KuisController::class, 'jawab'])->name('percobaan.jawab');
    Route::post('/percobaan/{percobaan}/serahkan', [App\Http\Controllers\Siswa\KuisController::class, 'serahkan'])->name('percobaan.serahkan');
    Route::get('/percobaan/{percobaan}/hasil', [App\Http\Controllers\Siswa\KuisController::class, 'hasil'])->name('percobaan.hasil');

    // Ganti Password Siswa
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
});

// Endpoint Detak Jantung (Heartbeat) - Auth (siswa dicatat, admin 204), Throttle 10 req/menit
Route::middleware(['auth', 'throttle:10,1'])->post('/detak', DetakJantungController::class)->name('siswa.detak');

// Penyaji PDF Privat (Bisa diakses Siswa Berhak atau Admin yang telah login)
Route::middleware('auth')->get('/materi-pdf/{materi}', PenyajiMateriPdfController::class)->name('materi.pdf.sajikan');

// Area Admin
Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/beranda', [AdminBerandaController::class, 'index'])->name('beranda');

    // Kelola Bab
    Route::resource('bab', BabController::class)->except(['show']);
    Route::post('bab/{bab}/naik', [BabController::class, 'naikkanUrutan'])->name('bab.naik');
    Route::post('bab/{bab}/turun', [BabController::class, 'turunkanUrutan'])->name('bab.turun');
    Route::post('bab/{bab}/terbit', [BabController::class, 'alihStatusTerbit'])->name('bab.terbit');

    // Kelola Materi per Bab
    Route::resource('bab.materi', MateriController::class)->except(['show']);
    Route::post('bab/{bab}/materi/{materi}/naik', [MateriController::class, 'naikkanUrutan'])->name('bab.materi.naik');
    Route::post('bab/{bab}/materi/{materi}/turun', [MateriController::class, 'turunkanUrutan'])->name('bab.materi.turun');

    // Kelola Kuis
    Route::resource('kuis', KuisController::class)->except(['show'])->parameters(['kuis' => 'kuis']);

    // Kelola Soal per Kuis
    Route::resource('kuis.soal', SoalController::class)->except(['show'])->parameters(['kuis' => 'kuis']);
    Route::post('kuis/{kuis}/soal/{soal}/naik', [SoalController::class, 'naikkanUrutan'])->name('kuis.soal.naik');
    Route::post('kuis/{kuis}/soal/{soal}/turun', [SoalController::class, 'turunkanUrutan'])->name('kuis.soal.turun');

    // Penilaian Esai
    Route::get('penilaian-esai', [PenilaianEsaiController::class, 'index'])->name('penilaian-esai.index');
    Route::get('penilaian-esai/{percobaan}', [PenilaianEsaiController::class, 'show'])->name('penilaian-esai.show');
    Route::post('penilaian-esai/{percobaan}', [PenilaianEsaiController::class, 'simpan'])->name('penilaian-esai.simpan');

    // Laporan Admin (Akses & Nilai)
    Route::get('laporan/akses', [LaporanAksesController::class, 'index'])->name('laporan.akses');
    Route::get('laporan/akses/ekspor', [LaporanAksesController::class, 'ekspor'])->name('laporan.akses.ekspor');
    Route::get('laporan/nilai', [LaporanNilaiController::class, 'index'])->name('laporan.nilai');
    Route::get('laporan/nilai/ekspor', [LaporanNilaiController::class, 'ekspor'])->name('laporan.nilai.ekspor');

    // Kelola Pengguna (Siswa)
    Route::get('pengguna/ganda', [PenggunaController::class, 'ganda'])->name('pengguna.ganda');
    Route::resource('pengguna', PenggunaController::class)->parameters(['pengguna' => 'pengguna']);
    Route::patch('pengguna/{pengguna}/toggle-aktif', [PenggunaController::class, 'toggleAktif'])->name('pengguna.toggle-aktif');
    Route::post('pengguna/{pengguna}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.reset-password');
    Route::post('pengguna/{pengguna}/reset-kesempatan', [PenggunaController::class, 'resetKesempatan'])->name('pengguna.reset-kesempatan');
});
