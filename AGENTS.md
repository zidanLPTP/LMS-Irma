# AGENTS.md — LMS Penelitian Sekolah

Instruksi tetap untuk agen di repositori ini. Baca sebelum mulai. Dokumen detail:

- @spec.md — apa yang dibangun (aturan bisnis, peran, alur). **Sumber kebenaran.**
- @styleguide.md — tampilan (warna, tipografi, komponen, ikon).

Urutan prioritas: `spec.md` > `styleguide.md` > skill terpasang > kebiasaan umum. Jika ada pertentangan, **berhenti dan tanyakan**, jangan memilih sendiri.

## 1. Proyek Singkat

LMS web untuk penelitian di sekolah. Admin (Irma) mengelola bab, materi, video YouTube, kuis. Sekitar 100 siswa SMA belajar berurutan: pretest → Bab 1–5 → posttest. Ada log akses (siapa, kapan, berapa lama), laporan di web, dan ekspor Excel. Kedua peran (siswa dan admin) harus bisa melakukan hal yang lazim di LMS (lihat `spec.md` §4; fitur inti dikerjakan di fase 1, fitur umum LMS di fase 2, **keduanya wajib selesai** dan sudah disetujui Irma). Semua antarmuka berbahasa Indonesia, nyaman di HP.

## 2. Stack (asumsi, lihat `spec.md` §3)

**Laravel 13** + MySQL/MariaDB, Blade + Tailwind + Alpine.js, `maatwebsite/excel`, PDF.js, ikon Lucide, Pest/PHPUnit, Playwright (hanya lokal). PHP lokal dan hosting sama-sama 8.4: atur `config.platform.php` = `8.4.0` di `composer.json`. Jika paket penting belum mendukung Laravel 13, pakai Laravel 12 dan beri tahu Fathin. Jangan menambah ekstensi PHP tanpa memastikan hosting mendukung. **Tanpa starter kit** (Breeze sudah tidak dikembangkan; jangan pakai kit React/Vue/Livewire): autentikasi memakai fitur bawaan Laravel dengan Blade sendiri, atau Fortify.

## 3. Batasan Hosting

Target produksi: shared hosting murah (LiteSpeed, cPanel, 2 GB). Karena itu:

- Tidak ada Node.js di server. Build aset di lokal (`npm run build`), sertakan hasilnya.
- Tidak ada queue worker, Redis, Supervisor. Pakai driver `sync`, `file`, atau `database`.
- Tidak mengandalkan LibreOffice atau binary luar. Video hanya embed YouTube.
- Hindari paket Composer berat. Jangan menulis ke luar `storage/` dan `public/` saat runtime.

## 4. Bahasa dan Penamaan (WAJIB)

- **Variabel, fungsi, kelas, tabel, kolom, nama route, nama berkas, nama folder, dan komponen yang dibuat proyek ini memakai Bahasa Indonesia yang sederhana.** Contoh: `hitungNilai()`, `batasWaktu`, kelas `Bab`, `Materi`, `Kuis`, `Soal`, `PercobaanKuis`, `LogAkses`, tabel `percobaan_kuis`, view `siswa/beranda.blade.php`, komponen `<x-tombol>`.
- **Komentar singkat dalam Bahasa Indonesia**, satu atau dua baris, menjelaskan *kenapa*, bukan mengulang kode.
- **Pengecualian:** nama yang diwajibkan framework/library tetap bawaan, supaya fitur tidak rusak: model `User` dan tabel `users` bawaan Laravel, kolom `email`, `password`, `created_at`, metode controller resource (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`), folder `config/`, `routes/web.php`, nama paket.
- Hindari singkatan yang membingungkan. Pakai satu istilah yang sama untuk satu hal (mis. selalu `siswa`, bukan campur `murid`).
- Teks yang tampil ke pengguna disimpan di berkas bahasa `lang/id`, bukan tersebar di Blade.

## 5. Skill Terpasang di agy (cara memakainya)

- **Superpowers:** untuk brainstorming, rencana terstruktur, dan **TDD (Merah-Hijau-Rapikan)**. Tulis tes dulu untuk logika bisnis (nilai, lulus, buka bab, percobaan, timer). Rencana singkat wajib sebelum perubahan besar.
- **UI UX Pro Max:** untuk menyempurnakan tata letak, interaksi, dan kenyamanan di HP. **Tidak boleh mengganti** palet, font, dan aturan ikon di `styleguide.md` (palet sudah ditetapkan sesuai permintaan Irma). Tampilan harus cocok untuk anak SMA: modern, tidak kekanak-kanakan.
- **Ponytail & Modern Web:** kode ringkas, tanpa bloatware, manfaatkan API native browser (`<dialog>`, `fetch`, `IntersectionObserver`, validasi HTML5). Ringkas tidak boleh mengorbankan keterbacaan. Utamakan Alpine.js daripada menambah pustaka.
- **OWASP Secure Coding:** cegah SQLi (Eloquent/binding), XSS (escape keluaran), CSRF (token), dan otorisasi per peran. **Validasi memakai Form Request Laravel**, bukan Zod (stack ini PHP). Password di-hash, bukan "dienkripsi".
- **Playwright Browser Testing:** uji alur nyata di browser untuk daftar, masuk, kuis (timer, 2 kesempatan), buka bab berurutan, penilaian esai, laporan. Hanya dependensi pengembangan lokal. Simpan tangkapan layar di `tests/e2e/hasil/` dan jangan di-commit.

## 6. Perintah

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
npm install && npm run dev      # pengembangan
npm run build                   # produksi
php artisan test                # tes logika
./vendor/bin/pint               # rapikan gaya kode
npx playwright test             # tes browser (lokal)
```

Jalankan tes dan `pint` sebelum menyatakan pekerjaan selesai.

## 7. Struktur yang Diharapkan

```
app/Http/Controllers/Admin/   # kelola bab, materi, kuis, nilai esai, laporan, pengguna
app/Http/Controllers/Siswa/   # beranda, bab, kuis, nilai, profil
app/Models/                   # Bab, Materi, Kuis, Soal, PilihanJawaban, PercobaanKuis, Jawaban, LogAkses, Pengumuman
app/Services/                 # PenilaianKuis, BukaAkses, PelacakAkses (logika bisnis)
app/Policies/                 # otorisasi per peran
resources/views/{admin,siswa,komponen}/
resources/svg/lucide/         # ikon
database/migrations, seeders/
tests/{Feature,Unit,e2e}/
```

Logika bisnis ditaruh di **Services**, bukan di controller atau Blade, supaya bisa diuji.

## 8. Aturan Bisnis yang Tidak Boleh Salah (ringkas dari `spec.md` §6–§9)

- Urutan: pretest → Bab 1–5 → posttest. Jumlah bab **dinamis**, bukan hardcode 5.
- Bab berikutnya terbuka jika kuis **lulus** ATAU **2 kesempatan habis**.
- **Lulus = nilai ≥ 65**, dihitung hanya dari soal otomatis (pilihan ganda dan isian). Esai dinilai manual admin, nilai akhir diperbarui setelahnya.
- 2 kesempatan per kuis bab, dipakai **nilai tertinggi**. Pretest dan posttest hanya sekali.
- Timer **60 detik per soal** hanya untuk pilihan ganda dan isian; esai tanpa batas. Timer divalidasi di **server**.
- Log akses mencatat **nama siswa, waktu akses, dan durasi** = total per sesi login (heartbeat), bukan per materi.
- Nilai lulus, durasi per soal, dan jumlah kesempatan disimpan sebagai **pengaturan**, bukan angka tertanam.
- Siswa hanya melihat data miliknya; hanya admin yang melihat dan mengekspor laporan.

## 9. Tampilan dan Ikon

- Ikon **hanya dari Lucide**, lewat `<x-ikon>`. **Tanpa emoji dan tanpa ikon bergaya template AI** (sparkles, wand, bot, roket, gradasi ungu-biru). Detail di `styleguide.md` §6.
- Pakai token warna, bukan hex mentah. Mobile-first. Bahasa teks sesuai `styleguide.md` §7.

## 10. Keamanan

- Jangan commit `.env`, kunci, atau data siswa asli; gunakan data palsu untuk seeder dan tes.
- Batas percobaan login, validasi semua input, validasi unggahan (tipe, ukuran), nama berkas dibuat sistem.
- Jangan kirim kunci jawaban atau perhitungan nilai ke klien.
- Kumpulkan data seminimal mungkin (pengguna anak SMA).

## 11. Cara Bekerja

1. Baca bagian relevan di `spec.md` dan `styleguide.md` sebelum mengubah apa pun.
2. Perubahan lebih dari satu berkas: tulis rencana singkat dan minta persetujuan dulu.
3. Kerja bertahap: satu fitur, tes, lanjut.
4. Hal bertanda **[ASUMSI]**, **[CEK]**, atau di "Pertanyaan Terbuka" yang menghambat: **tanyakan ke Fathin**, jangan menebak.
5. Jangan menambah fitur di luar `spec.md`. Fitur umum LMS dikerjakan setelah fase 1 stabil.
6. Jangan menjalankan perintah merusak (hapus data, `migrate:fresh` di produksi, `rm -rf`) tanpa konfirmasi.
7. Setelah selesai, ringkas apa yang diubah, apa yang diuji, dan apa yang belum.

## 12. Deploy (garis besar)

Build aset lokal, `composer install --no-dev --optimize-autoloader`, unggah (File Manager/FTP), arahkan document root ke `public/`, atur `.env` produksi (`APP_DEBUG=false`), migrasi, buat admin lewat seeder, aktifkan SSL dan paksa HTTPS. Jangan mengasumsikan akses SSH.
