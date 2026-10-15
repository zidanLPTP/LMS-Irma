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

## 2. Pencegahan IDOR (*Insecure Direct Object Reference*) dan Verifikasi Model Binding

Setiap rute yang memanfaatkan route model binding (`{user}`, `{bab}`, `{materi}`, `{kuis}`, `{soal}`, `{percobaan}`, `{notifikasi}`, `{pengumuman}`) telah diuji secara menyeluruh terhadap eksploitasi IDOR:

| Model Binding / Rute | Metode & URI | Mekanisme Proteksi Keamanan | Status Pengujian IDOR |
| :--- | :--- | :--- | :--- |
| **Percobaan Kuis** | `GET /siswa/percobaan/{percobaan}`<br>`GET /siswa/percobaan/{percobaan}/hasil` | Otorisasi kepemilikan ketat: `$percobaan->user_id === auth()->id()`. Akses percobaan siswa lain ditolak `403 Forbidden`. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `SiswaEngineKuisTest`) |
| **Soal Kuis** | `POST /siswa/percobaan/{percobaan}/soal/{soal}/mulai`<br>`POST /siswa/percobaan/{percobaan}/soal/{soal}/jawab` | Validasi kepemilikan sesi pengerjaan dan pencocokan `$soal->kuis_id === $percobaan->kuis_id`. Menjawab soal siswa lain ditolak `403`. | **TERUJI (LULUS)** (`SiswaEngineKuisTest`) |
| **Notifikasi Siswa** | `GET /siswa/notifikasi/{notifikasi}/baca` | Validasi user: `$notifikasi->user_id === auth()->id()`. Membuka notifikasi milik akun lain ditolak `403 Forbidden`. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `DasborPengumumanNotifikasiTest`) |
| **Bab Pembelajaran** | `GET /siswa/bab/{bab}` | Validasi pembukaan bab dinamis via `BukaAksesService::apakahBabTerbuka`. Upaya menebak ID bab terkunci dialihkan ke beranda dengan pesan galat ramah. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `SiswaBelajarTest`) |
| **Materi Pembelajaran** | `GET /siswa/bab/{bab}/materi/{materi}` | Verifikasi ganda: Bab harus terbuka DAN `$materi->bab_id === $bab->id`. Mengakses materi bab lain pada URL bab berbeda menghasilkan `404 Not Found`. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `SiswaBelajarTest`) |
| **Berkas PDF Privat** | `GET /materi/{materi}/pdf` | Pengecekan status rilis bab & ketersediaan hak belajar siswa. Mengakses berkas materi sebelum bab terbuka ditolak `403 Forbidden`. | **TERUJI (LULUS)** (`SiswaBelajarTest`, `Langkah15KeamananDanAuditTest`) |
| **Kuis Pembelajaran** | `GET /siswa/kuis/{kuis}`<br>`POST /siswa/kuis/{kuis}/mulai` | Validasi prasyarat belajar dan status rilis kuis via `BukaAksesService`. Kuis bab terkunci menolak percobaan baru. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `SiswaEngineKuisTest`) |
| **Pengumuman** | `GET /siswa/pengumuman/{pengumuman}` | Memfilter pengumuman agar hanya status `terbit` dan belum kedaluwarsa yang dapat dibuka. Pengumuman `draf` menghasilkan `404 Not Found`. Riwayat baca dicatat secara idempoten per pengguna. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `DasborPengumumanNotifikasiTest`) |
| **Kelola Pengguna Admin**| `GET/PUT /admin/pengguna/{user}` | Proteksi peran admin (`peran:admin` + `Gate::authorize`). Upaya siswa mengakses data pengguna lain ditolak instan `403 Forbidden`. | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `AdminPenggunaTest`) |
| **Penilaian Esai Admin** | `GET/POST /admin/penilaian-esai/{percobaan}` | Khusus peran admin. Siswa tidak dapat mengakses panel penilaian esai milik siapa pun (`403 Forbidden`). | **TERUJI (LULUS)** (`Langkah15KeamananDanAuditTest`, `AdminPenilaianEsaiTest`) |

---

## 3. Header Keamanan & Content Security Policy (CSP)
- **X-Content-Type-Options: `nosniff`** aktif pada seluruh respon web dan berkas statis/PDF untuk mencegah MIME-sniffing.
- **X-Frame-Options: `SAMEORIGIN`** mencegah clickjacking.
- **Referrer-Policy: `strict-origin-when-cross-origin`** melindungi kebocoran parameter URL pada rujukan luar.
- **Permissions-Policy: `camera=(), microphone=(), geolocation=()`** menonaktifkan fitur perangkat keras yang tidak dibutuhkan.
- **Kebijakan CSP Diperketat (Hardened CSP):**
  - `default-src 'self'`
  - `script-src 'self' 'unsafe-eval'` (Host pihak ketiga `https://cdn.jsdelivr.net` dan token `'unsafe-inline'` **DIHAPUS**. Seluruh pustaka JS termasuk Alpine.js dan penampil PDF kini dibundel 100% lokal via Vite. Skrip inline dipindahkan ke bundel asset).
  - `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com`
  - `font-src 'self' https://fonts.gstatic.com data:`
  - `img-src 'self' data:`
  - `frame-src 'self' https://www.youtube-nocookie.com`
  - `worker-src 'self' blob:` (diperlukan untuk web worker rendering PDF.js lokal)
  - `connect-src 'self'`

### Evaluasi Build CSP Alpine (@alpinejs/csp)
- **Status Saat Ini:** Alpine.js versi standar menggunakan evaluasi fungsi dinamis (`Function()`) untuk direktif `x-data`, `x-show`, `x-on`, sehingga memerlukan `'unsafe-eval'`.
- **Evaluasi `@alpinejs/csp`:** Build `@alpinejs/csp` meniadakan kebutuhan `'unsafe-eval'`, namun mensyaratkan pendaftaran eksplisit setiap komponen dan aksi logika ke dalam tabel handler JavaScript global (`Alpine.data(...)`), dan tidak mendukung ekspresi inline bebas (seperti `x-data="{ bukaMenuHp: false }"` atau `@click="bukaCariHp = false"`).
- **Alasan Teknis & Mitigasi Risiko:** Karena seluruh template Blade LMS BioSTEMX menggunakan sanitasi keluaran ketat (`{{ }}`) tanpa raw HTML (`{!! !!}` dari input pengguna), tidak ada celah Cross-Site Scripting (DOM XSS) yang dapat dimanfaatkan untuk menyuntikkan ekspresi eval berbahaya. Oleh sebab itu, direktif `'unsafe-eval'` dipertahankan bersama Alpine.js reguler lokal, sementara `'unsafe-inline'` dan host CDN eksternal berhasil dieliminasi sepenuhnya.

### Hasil Pemindaian Browser Otomatis (Playwright CSP Scan - Kebijakan Diperketat)
Pengujian E2E dijalankan melalui `tests/e2e/csp-audit.spec.js` pada dua resolusi standar (HP 375x667 dan Laptop 1280x800) untuk seluruh alur halaman utama admin dan siswa:
- Halaman Masuk (`/masuk`)
- Dasbor Admin (`/admin/beranda`)
- Laporan Nilai Admin (`/admin/laporan/nilai`)
- Beranda Siswa (`/siswa/beranda`)
- Nilai Saya Siswa (`/siswa/nilai-saya`)
- Profil Siswa (`/siswa/profil`)

**Hasil Pengujian:**
- **Resolusi HP (375x667):** **0 pelanggaran CSP** tercatat di konsol browser.
- **Resolusi Laptop (1280x800):** **0 pelanggaran CSP** tercatat di konsol browser.
- **Hasil:** Kebijakan CSP yang diperketat (tanpa host CDN dan tanpa `'unsafe-inline'`) berjalan mulus dan siap untuk penegakan penuh (*enforce mode*).

---

## 4. Perlindungan Unggahan Berkas (PDF & Excel)
1. **Validasi Tanda Awal Berkas (%PDF-):** Unggahan modul pembelajaran divalidasi tidak hanya berdasarkan ekstensi dan MIME type peramban, tetapi juga pembacaan 5 byte pertama berkas (`%PDF-`), mencegah penyamaran skrip executable menjadi berkas PDF.
2. **Pencegahan CSV/Excel Formula Injection:** Seluruh ekspor Excel menggunakan `AmanValueBinder` yang menetralkan karakter formula berisiko (`=`, `+`, `-`, `@`, tab, carriage return). Pada proses impor, rumus di lembar kerja ditolak eksplisit dengan pesan `"rumus tidak diizinkan"`.
