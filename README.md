# BioSTEMX — LMS Penelitian Biologi Sekolah

BioSTEMX adalah platform *Learning Management System* (LMS) berbasis web yang dirancang khusus untuk mendukung penelitian pembelajaran biologi di tingkat SMA. Platform ini memungkinkan siswa belajar secara mandiri, terstruktur, dan berurutan melalui pendekatan STEM (*Science, Technology, Engineering, and Mathematics*) dengan antarmuka yang ramah, modern, dan nyaman diakses melalui perangkat seluler (*mobile-first*).

---

## 🔬 Makna Nama & Identitas

- **Bio**: Penelitian materi biologi (ekosistem, genetika, sel, dan fenomena kehidupan).
- **STEM**: Pembelajaran terpadu sains, teknologi, rekayasa, dan matematika dengan alur berpikir analitis dan pemecahan masalah.
- **X (*Explorer*)**: Eksplorasi aktif dan mandiri oleh siswa melalui rangkaian bab, materi multimedia, dan evaluasi berkala.

---

## ✨ Fitur Utama

### 1. Pembelajaran Berurutan untuk Siswa
- **Alur Bertahap:** Pretest → Bab 1 sampai Bab N (dinamis) → Posttest.
- **Buka Akses Bab:** Bab berikutnya terbuka secara otomatis jika siswa **lulus kuis** (nilai ≥ 65 pada soal otomatis) **ATAU** telah menghabiskan **2 kesempatan** percobaan kuis.
- **Materi Interaktif:** Mendukung teks kaya, video YouTube (*privacy-enhanced nocookie*), berkas presentasi/modul PDF privat terproteksi, serta penanda progres "Tandai Selesai" mandiri.
- **Timer Kuis Server-Side:** Waktu pengerjaan (bawaan 60 detik per soal) divalidasi ketat di sisi server untuk mencegah manipulasi waktu di peramban.
- **Pencarian Materi:** Pencarian instan untuk menemukan topik dan materi pada bab-bab yang telah terbuka dan terbit.
- **Nilai Saya & Notifikasi:** Rekap riwayat percobaan nilai terbaik serta notifikasi penting (evaluasi esai guru, reset kesempatan, dan pengumuman sekolah).

### 2. Panel Pengelolaan untuk Guru / Peneliti (Admin)
- **Dasbor Statistik:** Pemantauan progres belajar 100+ siswa, rata-rata nilai, siswa aktif hari ini, dan aktivitas kuis terkini.
- **Manajemen Kurikulum:** Kelola bab, materi (teks/video/PDF), kuis, dan bank soal (Pilihan Ganda, Isian Singkat, dan Esai).
- **Penilaian Esai:** Antarmuka khusus untuk koreksi jawaban esai siswa disertai rubrik dan catatan umpan balik guru.
- **Impor Data Cepat (Excel):**
  - Impor akun siswa massal hingga 200 baris dengan pembuatan kata sandi acak atomik dan templat panduan.
  - Impor bank soal dari Excel lengkap dengan validasi integritas data.
- **Pelacakan & Audit Akses:** Rekapitulasi waktu masuk, durasi sesi belajar (*heartbeat/detak jantung* berkala), dan riwayat tindakan admin.
- **Ekspor Laporan (Excel):** Ekspor rekap akses dan nilai siswa ke dalam format `.xlsx` dengan formula aman (*neutralized formula injection*).
- **Pratinjau Sebagai Siswa:** Mode simulasi bagi guru untuk memeriksa tampilan materi dan kuis tanpa memengaruhi riwayat atau progres belajar nyata.
- **Kontrol Pendaftaran:** Opsi buka/tutup pendaftaran publik mandiri dan verifikasi kode pendaftaran khusus.

---

## 🛠️ Tumpukan Teknologi (*Tech Stack*)

- **Backend:** Laravel 13, PHP 8.4
- **Basis Data:** MySQL / MariaDB
- **Frontend:** Blade Templating, Tailwind CSS, Alpine.js, Ikon Lucide
- **Manipulasi Excel:** `maatwebsite/excel` & `PhpSpreadsheet`
- **Penyaji Dokumen:** PDF.js
- **Pengujian & Kualitas Kode:** PHPUnit, Laravel Pint, Playwright (E2E visual browser)

---

## 🚀 Panduan Instalasi Lokal

### 1. Kebutuhan Sistem
- PHP >= 8.4 (ekstensi `pdo_mysql`, `mbstring`, `gd` / `imagick`, `zip`, `xml`)
- Composer >= 2.x
- Node.js & NPM
- MySQL / MariaDB Server

### 2. Langkah Pemasangan
```bash
# 1. Gandakan repositori
git clone <url-repositori>
cd LMS

# 2. Pasang dependensi PHP
composer install

# 3. Siapkan berkas environment
cp .env.example .env
php artisan key:generate

# 4. Atur konfigurasi basis data di .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 5. Jalankan migrasi dan seeder awal
php artisan migrate --seed

# 6. Pasang dependensi JavaScript & bangun aset frontend
npm install
npm run build

# 7. Jalankan server lokal
php artisan serve
```

Akses sistem di peramban melalui alamat: `http://localhost:8000`

---

## 🧪 Pengujian & Standar Kode

Jalankan perintah berikut sebelum melakukan komit kode:

```bash
# Menjalankan seluruh pengujian logika bisnis (130+ Feature & Unit tests)
php artisan test

# Merapikan gaya penulisan kode sesuai standar Laravel Pint
./vendor/bin/pint

# Menjalankan pengujian E2E browser Playwright (opsional lokal)
npx playwright test
```

---

## 🔒 Keamanan & Prinsip Desain

- **Keamanan OWASP:** Perlindungan terhadap SQL Injection via Eloquent ORM, XSS dengan *output escaping*, CSRF token di setiap formulir, *rate limiting / throttling* pada rute sensitif, serta netralisasi injeksi formula pada lembar ekspor Excel.
- **Optimal Shared Hosting:** Dirancang hemat sumber daya (driver antrean `sync`/`database`, video embed YouTube tanpa beban transkoding lokal, tanpa dependensi background worker berat).
- **Bahasa & Istilah:** Seluruh antarmuka pengguna memakai Bahasa Indonesia yang baku, sederhana, dan ramah untuk pelajar sekolah menengah.

---

## 📄 Lisensi

Platform ini dikembangkan untuk keperluan penelitian pendidikan dan dilisensikan di bawah lisensi terbuka [MIT](LICENSE).
