<?php

use App\Http\Controllers\Admin\BabController;
use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\KuisController;
use App\Http\Controllers\Admin\MateriController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\MasukController;
use App\Http\Controllers\PenyajiMateriPdfController;
use App\Http\Controllers\Siswa\BerandaController as SiswaBerandaController;
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
Route::middleware(['auth', 'peran:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/beranda', [SiswaBerandaController::class, 'index'])->name('beranda');
    Route::get('/bab/{bab}', [App\Http\Controllers\Siswa\BabController::class, 'show'])->name('bab.show');
    Route::get('/bab/{bab}/materi/{materi}', [App\Http\Controllers\Siswa\MateriController::class, 'show'])->name('bab.materi.show');
});

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
});
