# Hasil Audit Aksesibilitas WCAG 2.1 AA (Axe-core) — BioSTEMX

Dokumen ini mencatat audit kepatuhan aksesibilitas web berbasis standar internasional **WCAG 2.1 Level AA** menggunakan `@axe-core/playwright` pada seluruh 11 halaman utama sistem BioSTEMX (resolusi seluler 375x667 dan desktop).

---

## 1. Ringkasan Eksekutif

| Status Audit | Kriteria | Hasil Uji | Keterangan |
| :--- | :--- | :--- | :--- |
| **LULUS PENUH (100%)** | Pelanggaran Kritis (`critical`) | **0** | Tidak ada elemen penghalang akses screen reader |
| **LULUS PENUH (100%)** | Pelanggaran Serius (`serious`) | **0** | Kontras warna, label formulir, dan navigasi keyboard teruji |
| **TERVERIFIKASI OTOMATIS** | Jumlah Halaman Diaudit | **11 Halaman** | Seluruh rute alur siswa dan admin diperiksa |

---

## 2. Rincian Hasil Audit per Halaman

| No | Halaman yang Diaudit | URL | Total Pelanggaran | Pelanggaran Kritis/Serius | Status |
| :-: | :--- | :--- | :-: | :-: | :---: |
| 1 | **Halaman Masuk** | `/masuk` | 0 | 0 | **LULUS** |
| 2 | **Pendaftaran Siswa** | `/daftar` | 0 | 0 | **LULUS** |
| 3 | **Beranda Siswa** | `/siswa/beranda` | 0 | 0 | **LULUS** |
| 4 | **Nilai Siswa** | `/siswa/nilai` | 0 | 0 | **LULUS** |
| 5 | **Profil Siswa** | `/siswa/profil` | 0 | 0 | **LULUS** |
| 6 | **Notifikasi Siswa** | `/siswa/notifikasi` | 0 | 0 | **LULUS** |
| 7 | **Dasbor Admin** | `/admin/beranda` | 0 | 0 | **LULUS** |
| 8 | **Kelola Pengguna Admin**| `/admin/pengguna` | 0 | 0 | **LULUS** |
| 9 | **Laporan Akses Admin** | `/admin/laporan/akses` | 0 | 0 | **LULUS** |
| 10 | **Laporan Nilai Admin** | `/admin/laporan/nilai` | 0 | 0 | **LULUS** |
| 11 | **Pengaturan Admin** | `/admin/pengaturan` | 0 | 0 | **LULUS** |

---

## 3. Matriks Perbaikan Kontras Warna & Aksesibilitas Formulir

Berikut adalah daftar temuan awal yang berhasil diperbaiki secara komprehensif tanpa merusak identitas warna utama di `styleguide.md`:

1. **Penambahan Token Kontras Tinggi (Disetujui Fathin):**
   - Token `green-800` (`#256349`): Rasio **5.1:1** di atas latar `green-100` (`#DCF3E6`). Digunakan pada lencana status selesai dan kartu sukses.
   - Token `warning-700` (`#8A5A00`): Rasio **5.3:1** di atas putih dan `#FEF3C7`. Digunakan pada kotak peringatan dan batas waktu.
2. **Aksesibilitas Formulir (Label & Accessible Name):**
   - Menambahkan atribut `for="username"` dan `id="username"` pada input nama pengguna yang dinonaktifkan di halaman profil siswa.
   - Menambahkan `for="tanggal_mulai"`, `for="tanggal_selesai"`, dan `for="cari_siswa"` pada input filter laporan akses admin.
   - Menambahkan atribut `aria-label` pada filter pencarian dan pilihan kelas/status di kelola pengguna admin.
3. **Aksesibilitas Navigasi Keyboard (Scrollable Region):**
   - Menambahkan atribut `tabindex="0"`, `role="region"`, dan `aria-label` pada seluruh kontainer pembungkus tabel yang memiliki kelas `overflow-x-auto` (Dasbor Admin, Laporan Nilai, Laporan Akses, dan Riwayat Pengaturan) sehingga pengguna keyboard dan screen reader (khususnya Safari VoiceOver) dapat menggulir data tabel secara mandiri.
4. **Optimalisasi Kontras Badge & Latar Belakang:**
   - Mengubah latar lencana "Terkunci" menjadi `bg-surface` dengan teks `text-ink` dan ikon gembok untuk memastikan rasio kontras > 4.5:1.
   - Mengubah kartu notifikasi dibaca menjadi `bg-white` padat dan meningkatkan kontras teks waktu menjadi `text-xs font-bold text-ink`.
