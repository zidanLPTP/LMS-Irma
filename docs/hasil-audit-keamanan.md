# Hasil Audit Keamanan BioSTEMX

Dokumen ini mendokumentasikan hasil pengujian dan audit keamanan mendalam untuk sistem LMS BioSTEMX, mencakup pencegahan Mass Assignment, proteksi IDOR (*Insecure Direct Object Reference*), keamanan Content-Security-Policy (CSP), serta perlindungan dokumen privat.

---

## 1. Audit Mass Assignment ($fillable) Seluruh Model

Berikut adalah daftar atribut `$fillable` pada setiap model Eloquent dalam sistem:

| Model | Daftar `$fillable` | Proteksi Kolom Kritis |
| :--- | :--- | :--- |
| `User` | `nama_lengkap`, `username`, `email`, `password`, `peran`, `kelas`, `aktif`, `wajib_ganti_password` | Atribut `peran`, `aktif`, dan `wajib_ganti_password` **TIDAK PERNAH** diambil langsung dari `$request->all()` pada registrasi/profil siswa. Controller menggunakan Form Request tervalidasi dan secara eksplisit menyetel `peran => 'siswa'`, `aktif => true`. |
| `PercobaanKuis` | `kuis_id`, `user_id`, `nomor_percobaan`, `waktu_mulai`, `waktu_selesai`, `status_pengerjaan`, `status_kelulusan`, `esai_menunggu_nilai`, `dibatalkan`, `dibatalkan_oleh`, `dibatalkan_pada`, `alasan_pembatalan`, `nilai_otomatis`, `nilai_akhir`, `urutan_soal_teracak` | Atribut `status_kelulusan`, `dibatalkan`, `nilai_otomatis`, dan `nilai_akhir` dikendalikan 100% oleh kalkulasi server (`EngineKuisService`, `PenilaianEsaiService`), tidak dapat disuntikkan oleh siswa melalui HTTP request. |
| `JawabanSiswa` | `percobaan_kuis_id`, `soal_id`, `pilihan_jawaban_id`, `teks_jawaban`, `waktu_mulai_soal`, `waktu_jawab`, `apakah_benar`, `nilai_poin`, `ragu_ragu` | `apakah_benar` dan `nilai_poin` dihitung murni di server. Siswa hanya mengirimkan ID pilihan atau teks jawaban yang divalidasi dengan timer server. |
| `Bab` | `judul`, `deskripsi`, `urutan`, `status_terbit` | Dikelola melalui `BabRequest` khusus peran admin. |
| `Materi` | `bab_id`, `judul`, `tipe`, `berkas_pdf`, `url_youtube`, `urutan` | Divalidasi lewat `MateriRequest`, upload berkas dibatasi ekstensi dan tanda awal berkas `%PDF-`. |
| `Kuis` | `bab_id`, `judul`, `deskripsi`, `tipe_kuis`, `nilai_lulus_penimpa`, `batas_waktu_per_soal_penimpa`, `acak_soal` | Dikelola lewat `KuisRequest` khusus peran admin. |
| `Soal` | `kuis_id`, `tipe`, `pertanyaan`, `penjelasan`, `bobot`, `urutan` | Dikelola lewat `SoalRequest` admin. |
| `PilihanJawaban` | `soal_id`, `label`, `teks_pilihan`, `apakah_kunci`, `urutan` | Kunci jawaban hanya dapat ditentukan oleh admin dan tidak bocor ke HTML siswa. |
| `LogAkses` | `user_id`, `waktu_masuk`, `aktivitas_terakhir`, `durasi_detik`, `ip_address`, `user_agent` | Durasi dan aktivitas dikelola server-side via `PelacakAksesService` dengan verifikasi jeda waktu nyata (anti-tampering). |
| `Notifikasi` | `user_id`, `tipe`, `judul`, `pesan`, `tautan`, `referensi_unik`, `dibaca_pada` | Notifikasi di-dispatch oleh sistem/admin, siswa hanya berhak menandai `dibaca_pada` miliknya sendiri. |
| `Pengumuman` | `judul`, `isi`, `disematkan`, `tanggal_terbit`, `tanggal_berakhir`, `dibuat_oleh` | Dikelola lewat `SimpanPengumumanRequest` admin. |
| `Pengaturan` | `kunci`, `nilai` | Dikelola lewat `SimpanPengaturanRequest` admin. |
| `RiwayatTindakanAdmin`| `admin_id`, `siswa_id`, `jenis`, `keterangan` | Ditulis otomatis oleh controller tindakan admin. |
| `RiwayatPengaturan` | `kunci`, `nilai_lama`, `nilai_baru`, `admin_id` | Ditulis otomatis saat perubahan pengaturan sistem. |
| `RiwayatProfil` | `user_id`, `bidang`, `nilai_lama`, `nilai_baru` | Ditulis otomatis saat profil siswa diperbarui. |

---

## 2. Pencegahan IDOR (*Insecure Direct Object Reference*)
1. **Percobaan Kuis:** Diperiksa kepemilikannya di `EngineKuisService` dan controller (`$percobaan->user_id !== auth()->id() -> abort(403)`). Siswa A sama sekali tidak dapat melihat pertanyaan, menjawab soal, atau melihat hasil percobaan siswa B.
2. **Notifikasi:** `NotifikasiController` memvalidasi `$notifikasi->user_id === auth()->id()`, upaya membuka notifikasi orang lain ditolak `403 Forbidden`.
3. **Penyajian Berkas PDF Privat:** `PenyajiMateriPdfController` memeriksa hak akses siswa melalui `BukaAksesService`. Bila bab terkait belum terbuka bagi siswa yang bersangkutan, server mengembalikan respon `403 Forbidden`.
4. **Penebakan ID Admin:** Seluruh rute manajemen admin dilindungi middleware `peran:admin` dan `Gate::authorize`. Upaya siswa memanggil URL admin menghasilkan penolakan instan.

---

## 3. Header Keamanan & Content Security Policy (CSP)
- **X-Content-Type-Options: `nosniff`** aktif pada seluruh respon web dan berkas statis/PDF untuk mencegah MIME-sniffing.
- **X-Frame-Options: `SAMEORIGIN`** mencegah clickjacking.
- **Referrer-Policy: `strict-origin-when-cross-origin`** melindungi kebocoran parameter URL pada rujukan luar.
- **Permissions-Policy: `camera=(), microphone=(), geolocation=()`** menonaktifkan fitur perangkat keras yang tidak dibutuhkan.
- **CSP (Content-Security-Policy-Report-Only):**
  - `default-src 'self'`
  - `frame-src 'self' https://www.youtube-nocookie.com`
  - `worker-src 'self' blob:` (diperlukan untuk worker rendering PDF.js)
  - `img-src 'self' data:`
  - `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com`
  - `font-src 'self' https://fonts.gstatic.com data:`
  - `script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net`
  - *Catatan Alpine.js:* Alpine.js versi standar memerlukan evaluasi ekspresi reaktif (`unsafe-eval`). Karena LMS tidak mengizinkan masukan HTML bebas dari siswa tanpa escaping, risiko injeksi DOM diminimalkan.

### Hasil Pemindaian Browser Otomatis (Playwright CSP Scan)
Pengujian E2E dijalankan melalui `tests/e2e/csp-audit.spec.js` pada dua resolusi standar (HP 375x667 dan Laptop 1280x800) untuk seluruh halaman utama admin dan siswa:
- Halaman Masuk (`/masuk`)
- Dasbor Admin (`/admin/beranda`)
- Laporan Nilai Admin (`/admin/laporan/nilai`)
- Beranda Siswa (`/siswa/beranda`)
- Nilai Saya Siswa (`/siswa/nilai-saya`)
- Profil Siswa (`/siswa/profil`)

**Hasil Pengujian:**
- Total Pelanggaran CSP Resolusi HP (375x667): **0 pelanggaran** (Lolos 100%)
- Total Pelanggaran CSP Resolusi Laptop (1280x800): **0 pelanggaran** (Lolos 100%)
- Semua aset skrip Alpine.js, Tailwind, ikon Lucide, dan stylesheet ter-load sempurna tanpa pelanggaran console CSP.

---

## 4. Perlindungan Unggahan Berkas (PDF & Excel)
1. **Validasi Tanda Awal Berkas (%PDF-):** Unggahan modul pembelajaran divalidasi tidak hanya berdasarkan ekstensi dan MIME type peramban, tetapi juga pembacaan 5 byte pertama berkas (`%PDF-`), mencegah penyamaran skrip executable menjadi berkas PDF.
2. **Pencegahan CSV/Excel Formula Injection:** Seluruh ekspor Excel menggunakan `AmanValueBinder` yang menetralkan karakter formula berisiko (`=`, `+`, `-`, `@`, tab, carriage return). Pada proses impor, rumus di lembar kerja ditolak eksplisit dengan pesan `"rumus tidak diizinkan"`.
