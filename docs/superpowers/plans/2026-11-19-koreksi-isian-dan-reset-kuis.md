# Rencana Implementasi: Koreksi Jawaban Isian Singkat & Reset Riwayat Kuis

> **Tujuan:** 
> 1. Memperbaiki bug mesin penilaian otomatis agar kunci soal isian dari Impor Excel terbaca 100%.
> 2. Menghadirkan halaman Koreksi Isian Singkat di Panel Admin dengan tombol cepat nilai (100%, 80%, 50%, 0%) dan input manual untuk kemudahan Irma.
> 3. Menambahkan fitur "Reset Riwayat Pengerjaan Kuis" sehingga kuis yang salah input dapat dibersihkan dan kembali bisa diedit/dihapus.

---

## 1. Perubahan Berkas & Tanggung Jawab

| Berkas | Aksi | Deskripsi |
|---|---|---|
| `app/Services/ImporSoalService.php` | Modifikasi | Buat rekaman `PilihanJawaban` saat impor soal isian dari Excel agar selaras dengan form web. |
| `app/Services/PenilaianKuisService.php` | Modifikasi | Baca kunci isian dari tabel `pilihan_jawaban` dan fallback ke kolom `soal.kunci_jawaban` (impor Excel). Tambahkan metode `hitungUlangNilaiPercobaan()`. |
| `app/Http/Controllers/Admin/KuisController.php` | Modifikasi | Tambahkan metode `resetPengerjaan(Kuis $kuis)` untuk menghapus riwayat percobaan kuis secara aman. |
| `app/Http/Controllers/Admin/KoreksiIsianController.php` | Baru | Controller khusus admin untuk melihat dan mengoreksi nilai isian singkat siswa secara cepat. |
| `routes/web.php` | Modifikasi | Daftarkan rute reset pengerjaan kuis dan rute koreksi isian singkat. |
| `resources/views/admin/kuis/index.blade.php` | Modifikasi | Tambahkan tombol aksi "Reset Riwayat Pengerjaan" pada kuis yang sudah terisi. |
| `resources/views/admin/koreksi-isian/index.blade.php` | Baru | Tampilan daftar jawaban isian siswa dengan tombol cepat (100%, 80%, 50%, 0%) dan input manual. |
| `resources/views/components/layouts/admin.blade.php` | Modifikasi | Tambahkan tautan menu "Koreksi Isian" di sidebar admin. |
| `tests/Feature/KoreksiIsianDanResetKuisTest.php` | Baru | Pengujian fitur penilaian isian impor Excel, koreksi manual isian, dan reset kuis. |

---

## 2. Rincian Langkah Kerja

- [ ] **Langkah 1: Perbaiki Pembacaan Kunci Isian (Impor Excel & Fallback)**
  - Sinkronkan `ImporSoalService` agar membuat `PilihanJawaban` untuk soal isian.
  - Perbarui `PenilaianKuisService` agar membaca kunci dari `PilihanJawaban` maupun `soal.kunci_jawaban`.
  - Tambahkan metode `hitungUlangNilaiPercobaan(PercobaanKuis $percobaan)` di `PenilaianKuisService`.

- [ ] **Langkah 2: Fitur Reset Pengerjaan Kuis**
  - Buat metode `resetPengerjaan` di `KuisController` dengan proteksi peran admin dan database transaction.
  - Daftarkan rute `POST /admin/kuis/{kuis}/reset-pengerjaan`.
  - Perbarui tombol di `resources/views/admin/kuis/index.blade.php` dengan dialog konfirmasi.

- [ ] **Langkah 3: Halaman & Controller Koreksi Isian Singkat**
  - Buat `KoreksiIsianController` (daftar jawaban isian siswa & simpan koreksi nilai).
  - Daftarkan rute `GET /admin/koreksi-isian` dan `POST /admin/koreksi-isian/{jawaban}`.
  - Buat view `resources/views/admin/koreksi-isian/index.blade.php` dengan tombol cepat (100%, 80%, 50%, 0%).
  - Tautkan menu di sidebar admin `resources/views/components/layouts/admin.blade.php`.

- [ ] **Langkah 4: Pengujian & Verifikasi**
  - Tulis tes fitur di `tests/Feature/KoreksiIsianDanResetKuisTest.php`.
  - Verifikasi sintaks PHP dan pastikan lulus.
  - Commit dan push ke remote `origin/main`.
