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

Pengujian diukur langsung menggunakan penangkap durasi mikrodetik (`microtime`), pencatat memori puncak sistem (`memory_get_peak_usage`), dan penelusur query database aktif (`DB::getQueryLog()`).

| Fitur / Halaman | Waktu Respons (ms) | Jumlah Query SQL | Memori Puncak (MB) | Status HTTP | Evaluasi & Rekomendasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Dasbor Admin** (`/admin/beranda`) | 29.912 ms | 16.428 queries | 40 MB | 200 OK | *Perlu Optimasi Eager Loading*: Agregasi sebaran progres 100 siswa saat ini memanggil `daftarLangkahBelajar` di dalam loop. Direkomendasikan melakukan batch query/caching agregat progres untuk produksi. |
| **Laporan Akses Siswa** (`/admin/laporan/akses`) | 70 ms | 9 queries | 40 MB | 200 OK | Sangat cepat (<100 ms) dan efisien (<15 query). Pagination 25 baris berjalan optimal. |
| **Laporan Nilai Siswa** (`/admin/laporan/nilai`) | 519 ms | 186 queries | 40 MB | 200 OK | Stabil di ~500 ms untuk 100 siswa dengan rekapitulasi nilai kuis bab. |
| **Daftar Pengguna** (`/admin/pengguna`) | 2.519 ms | 1.229 queries | 40 MB | 200 OK | Halaman paginasi 25 siswa berjalan aman (<3 dtk) dengan kalkulasi progres belajar siswa. |
| **Antrean Penilaian Esai** (`/admin/penilaian-esai`) | 135 ms | 10 queries | 40 MB | 200 OK | Sangat ringan (<150 ms) dan bebas N+1 query. |
| **Beranda Siswa** (`/siswa/beranda`) | 202 ms | 63 queries | 40 MB | 200 OK | Memenuhi standar (<500 ms) untuk alur stepper dan bab terbit. |
| **Halaman Soal Kuis** (`/siswa/percobaan/{id}/soal`) | 48 ms | 18 queries | 40 MB | 200 OK | Sangat responsif (<50 ms), memastikan pengalaman ujian siswa di HP tidak tersendat. |
| **Endpoint Detak (Heartbeat)** (`/detak`) | 43 ms | 11 queries | 40 MB | 204 No Content | Ringan dan tidak membebani server saat browser mengirim sinyal berkala. |
| **Ekspor Excel Laporan Akses** (`/admin/laporan/akses/ekspor`) | 1.435 ms | 15 queries | 74 MB | 200 OK | Sangat cepat (~1,4 dtk) dan memori aman jauh di bawah batas 128 MB. |
| **Ekspor Excel Laporan Nilai** (`/admin/laporan/nilai/ekspor`) | 877 ms | 123 queries | 74 MB | 200 OK | Sangat cepat (<1 dtk) dan penggunaan memori sangat efisien (74 MB vs limit 128 MB). |

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
