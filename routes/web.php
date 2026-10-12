<?php

use App\Http\Controllers\Admin\BabController;
use App\Http\Controllers\Admin\BerandaController as AdminBerandaController;
use App\Http\Controllers\Admin\ImporSiswaController;
use App\Http\Controllers\Admin\ImporSoalController;
use App\Http\Controllers\Admin\KuisController;
use App\Http\Controllers\Admin\LaporanAksesController;
use App\Http\Controllers\Admin\LaporanNilaiController;
use App\Http\Controllers\Admin\MateriController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PenilaianEsaiController;
use App\Http\Controllers\Admin\PratinjauController;
use App\Http\Controllers\Admin\SoalController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\MasukController;
use App\Http\Controllers\PenyajiMateriPdfController;
use App\Http\Controllers\Siswa\BerandaController as SiswaBerandaController;
use App\Http\Controllers\Siswa\DetakJantungController;
use App\Http\Controllers\Siswa\NilaiSayaController;
use App\Http\Controllers\Siswa\NotifikasiController;
use App\Http\Controllers\Siswa\PasswordController;
use App\Http\Controllers\Siswa\PencarianController;
use App\Http\Controllers\Siswa\PengumumanController;
use App\Http\Controllers\Siswa\ProfilController;
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
    Route::get('/cari', PencarianController::class)->middleware('throttle:30,1')->name('cari');
    Route::get('/bab/{bab}', [App\Http\Controllers\Siswa\BabController::class, 'show'])->name('bab.show');
    Route::get('/bab/{bab}/materi/{materi}', [App\Http\Controllers\Siswa\MateriController::class, 'show'])->name('bab.materi.show');
    Route::post('/bab/{bab}/materi/{materi}/selesai', [App\Http\Controllers\Siswa\MateriController::class, 'toggleSelesai'])->name('bab.materi.toggle-selesai');

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

    // Profil Siswa
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    // Rekapitulasi Nilai Siswa
    Route::get('/nilai', [NilaiSayaController::class, 'index'])->name('nilai.index');

    // Pengumuman Siswa
    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::get('/pengumuman/{pengumuman}', [PengumumanController::class, 'show'])->name('pengumuman.show');

    // Notifikasi Siswa
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notifikasi}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');
});

// Endpoint Detak Jantung (Heartbeat) - Auth (siswa dicatat, admin 204), Throttle 10 req/menit
Route::middleware(['auth', 'throttle:10,1'])->post('/detak', DetakJantungController::class)->name('siswa.detak');

// Penyaji PDF Privat (Bisa diakses Siswa Berhak atau Admin yang telah login)
Route::middleware('auth')->get('/materi-pdf/{materi}', PenyajiMateriPdfController::class)->name('materi.pdf.sajikan');

// Area Admin
Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/beranda', [AdminBerandaController::class, 'index'])->name('beranda');

    // Pengaturan Sistem
    Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

    // Pengumuman Admin
    Route::resource('pengumuman', App\Http\Controllers\Admin\PengumumanController::class)->except(['show']);

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

    // Impor Siswa dari Excel
    Route::get('impor-siswa/templat', [ImporSiswaController::class, 'unduhTemplat'])->name('impor-siswa.templat');
    Route::get('impor-siswa', [ImporSiswaController::class, 'create'])->name('impor-siswa.create');
    Route::post('impor-siswa/pratinjau', [ImporSiswaController::class, 'pratinjau'])->name('impor-siswa.pratinjau');
    Route::post('impor-siswa/konfirmasi', [ImporSiswaController::class, 'konfirmasi'])->name('impor-siswa.konfirmasi');
    Route::get('impor-siswa/unduh-akun', [ImporSiswaController::class, 'unduhAkun'])->name('impor-siswa.unduh-akun');

    // Impor Soal Kuis dari Excel
    Route::get('impor-soal/templat', [ImporSoalController::class, 'unduhTemplat'])->name('impor-soal.templat');
    Route::get('kuis/{kuis}/impor-soal', [ImporSoalController::class, 'create'])->name('impor-soal.create');
    Route::post('kuis/{kuis}/impor-soal/pratinjau', [ImporSoalController::class, 'pratinjau'])->name('impor-soal.pratinjau');
    Route::post('kuis/{kuis}/impor-soal/konfirmasi', [ImporSoalController::class, 'konfirmasi'])->name('impor-soal.konfirmasi');

    // Mode Pratinjau Sebagai Siswa oleh Admin
    Route::prefix('pratinjau')->name('pratinjau.')->group(function () {
        Route::get('bab/{bab}', [PratinjauController::class, 'bab'])->name('bab');
        Route::get('bab/{bab}/materi/{materi}', [PratinjauController::class, 'materi'])->name('materi');
        Route::get('kuis/{kuis}', [PratinjauController::class, 'kuis'])->name('kuis');
    });
});
