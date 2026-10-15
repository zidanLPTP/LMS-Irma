# Laporan Uji Performa dan Analisis Beban (Langkah 15)

Dokumen ini mencatat tolak ukur (benchmark) performa sistem LMS BioSTEMX pada lingkungan dengan data simulasi beban penuh (**100 akun siswa SMA**, **5 Bab aktif**, materi PDF & video YouTube, soal pilihan ganda, isian, dan esai, serta ribuan riwayat log akses dan percobaan kuis).

---

## 1. Lingkungan Pengujian
- **Sistem Operasi**: Windows 11
- **Web Server / PHP**: PHP 8.4 CLI / PHP Built-in Server (Target Produksi: Shared Hosting LiteSpeed / cPanel 2 GB RAM)
- **Basis Data**: MySQL 8.x (`lms_penelitian`)
- **Seeder Beban**: `Database\Seeders\BebanUjiSeeder`
  - 100 Siswa aktif (`siswa_beban_1` s/d `siswa_beban_100`)
  - 5 Bab terbit dengan materi & kuis
  - 1.500+ entri `log_akses` (simulasi riwayat 15 hari aktivitas belajar)
  - Puluhan riwayat `percobaan_kuis` beserta antrean esai

---

## 2. Hasil Pengukuran Tolak Ukur (Benchmark)

### A. Tabel Perbandingan Sebelum vs Sesudah Optimasi Batch & In-Memory Caching

Optimasi meliputi:
1. `BukaAksesService::daftarLangkahBelajarMassal`: Menggantikan perulangan `daftarLangkahBelajar` 100 siswa di dasbor admin dan 25 siswa di daftar pengguna menjadi 4 batch query dengan evaluasi status di memori.
2. In-Memory Static Caching `Pengaturan::ambilSemuaCached`: Menghilangkan query `pengaturan` yang berulang pada perhitungan nilai lulus dan batas waktu.
3. Batch agregasi `statistikKuis` di `BerandaController`: Satu query ringkasan `groupBy('kuis_id', 'user_id')` untuk seluruh kuis.
4. Eliminasi query berulang `ambilNilaiTertinggi` di Laporan Nilai Web & Sheet Excel menggunakan koleksi relasi `percobaanKuis` yang sudah di-eager load.
5. Delegasi `daftarLangkahBelajar` tunggal ke `daftarLangkahBelajarMassal([$user])` pada Beranda Siswa.

| Fitur / Halaman | Target Bagian C | Sebelum Optimasi (Waktu / Query) | Sesudah Optimasi (Waktu / Query) | Status Evaluasi |
| :--- | :--- | :--- | :--- | :--- |
| **Dasbor Admin** (`/admin/beranda`) | **< 500 ms** & **< 25 query** | 29.912 ms / 16.428 queries | **213,13 ms / 22 queries** | **TERCAPAI** (Turun 99,8% query & 99,3% waktu) |
| **Daftar Pengguna** (`/admin/pengguna` - 25 baris) | **< 30 query** | 2.519 ms / 1.229 queries | **81,53 ms / 11 queries** | **TERCAPAI** (Turun 99,1% query & 96,8% waktu) |
| **Beranda Siswa** (`/siswa/beranda`) | **< 25 query** | 202 ms / 63 queries | **32,60 ms / 10 queries** | **TERCAPAI** (Turun 84,1% query & 83,9% waktu) |
| **Laporan Nilai Siswa** (`/admin/laporan/nilai`) | **< 30 query** | 519 ms / 186 queries | **71,24 ms / 10 queries** | **TERCAPAI** (Turun 94,6% query & 86,3% waktu) |
| **Ekspor Excel Rekap Nilai** (`RekapNilaiSheet`) | **< 30 query** | 877 ms / 123 queries | **97,04 ms / 6 queries** | **TERCAPAI** (Turun 95,1% query & 88,9% waktu) |
| **Endpoint Detak (Heartbeat)** (`/detak`) | **< 4 query** | 43 ms / 11 queries | **10,61 ms / 2 queries** | **TERCAPAI** (Hanya 2 query: cek session & update log) |

---

## 3. Hasil Pengujian Tahap Konfirmasi Impor Excel (Akun Siswa)

Sesuai instruksi Langkah 14 bagian A3, tahap konfirmasi impor (pembuatan akun siswa dengan hash kata sandi `Hash::make()` menggunakan `BCRYPT_ROUNDS=12` bawaan Laravel) telah diuji secara terukur:

1. **Uji 200 Baris Siswa**:
   - Waktu proses: **72.477,63 ms (72,48 detik)**.
   - Sesuai aturan keputusan: Karena durasi melebihi batas 10 detik, batas maksimum baris impor diturunkan menjadi **100 baris per berkas**.
   - **Keputusan**: Biaya hash (`BCRYPT_ROUNDS`) **TIDAK diturunkan** (tetap aman standar industri 12).
2. **Uji 100 Baris Siswa (Batas Baru)**:
   - Waktu proses: **35.069,37 ms (35,07 detik)**.
   - Transaksi database dipastikan tetap atomik (`DB::transaction`) sehingga jika proses terhenti sebelum tuntas, tidak akan meninggalkan data akun setengah jadi.

---

## 4. Keamanan Paket (Audit)
- **Composer Security Audit**:
  ```bash
  composer audit
  # Hasil: No security vulnerability advisories found.
  ```
- **NPM Security Audit**:
  ```bash
  npm audit
  # Hasil: found 0 vulnerabilities
  ```
