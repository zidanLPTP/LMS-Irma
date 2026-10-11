# SPEC — LMS Penelitian Sekolah

Dokumen ini adalah sumber kebenaran untuk apa yang dibangun. Tanda **[ASUMSI]** berarti belum dikonfirmasi Irma. Tanda **[CEK]** berarti perlu diperiksa dulu (biasanya soal hosting). Bagian **Pertanyaan Terbuka** di akhir harus diselesaikan sebelum rilis.

## 1. Ringkasan

LMS (Learning Management System) berbasis web, mirip e-campus, untuk penelitian Irma di sekolah. Irma (admin) menambahkan materi, video, dan kuis. Sekitar 100 siswa belajar bab demi bab, mengerjakan kuis, dan melihat nilai. Sistem mencatat siapa saja yang mengakses, kapan, dan berapa lama.

- Pengguna: 1 admin (Irma) dan sekitar 100 siswa (pengguna umum).
- Akses: HP dan laptop (responsif).
- Target pengguna siswa: **anak SMA** (sekitar 15–18 tahun). Tampilan modern dan bersih, bahasa santai tapi sopan, dan harus nyaman di HP.
- Bahasa antarmuka: Bahasa Indonesia penuh.
- Sistem lama (Google Sites) tidak punya kode, jadi dibangun dari nol.
- Pembuat: Fathin (hanya membangun). Perawatan selama penelitian dipegang Irma.
- Sistem akan diuji coba dengan siswa.

## 2. Batasan Teknis (shared hosting)

Hosting yang direncanakan adalah shared hosting murah (LiteSpeed, cPanel, PHP 7.x–8.x, 2 GB storage, 1 domain, SSL gratis). Dampaknya:

- Tidak ada runtime Node.js di server produksi. Aset frontend dibuild di lokal lalu diunggah.
- Tidak bisa mengandalkan LibreOffice atau software server tambahan, jadi PPT tidak dikonversi otomatis di server.
- Storage terbatas 2 GB, jadi video **tidak diunggah**, hanya embed YouTube. File materi dibatasi ukurannya.
- Tidak ada worker antrean. Semua proses harus ringan dan sinkron.
- Hosting mendukung **PHP 8.4** (ea-php84), sama dengan PHP lokal Fathin. Yang masih **[CEK]**: apakah ada MySQL/MariaDB, akses SSH/Composer, dan batas memori PHP.
- Hosting dan domain diurus belakangan. Spesifikasi ini harus tetap berjalan di paket murah tersebut.

## 3. Tech Stack **[ASUMSI]**

Dipilih agar cocok dengan shared hosting PHP:

- Backend: **Laravel 13** (butuh PHP 8.3–8.5; dukungan keamanan sampai sekitar 2028) dengan MySQL/MariaDB. Dipilih karena hosting dan lokal sama-sama PHP 8.4. Di `composer.json` atur `config.platform.php` ke `8.4.0` supaya Composer memilih paket yang cocok dengan hosting. Bila ada paket penting (mis. `maatwebsite/excel`) yang belum mendukung Laravel 13, gunakan **Laravel 12** (PHP 8.2–8.5) sebagai cadangan dan beri tahu Fathin.
- Frontend: Blade + Tailwind CSS (v4, sesuai bawaan skeleton Laravel terbaru) + Alpine.js, dibuild lokal dan hasil build ikut diunggah.
- Autentikasi: **tanpa starter kit** (Breeze sudah tidak dikembangkan, dan starter kit baru berbasis React/Vue/Livewire). Pakai autentikasi bawaan Laravel dengan tampilan Blade sendiri (masuk, daftar, keluar, ganti password), atau Laravel Fortify bila lebih sederhana. Agen memilih dan menjelaskan alasannya di rencana.
- Ekspor Excel: paket `maatwebsite/excel` (PhpSpreadsheet).
- Tampilan PDF materi: PDF.js di sisi browser.
- Ikon: **Lucide** (SVG inline). Tanpa emoji dan tanpa ikon bergaya template AI (lihat `styleguide.md`).
- Validasi: Form Request Laravel di server (Zod tidak dipakai karena bukan stack JavaScript); validasi HTML5 native di sisi klien.
- Pengujian: Pest/PHPUnit untuk logika, Playwright untuk alur di browser (hanya di lokal, tidak dipasang di hosting).
- Deploy: unggah lewat File Manager/FTP atau Git, `vendor/` dibuat lokal dan ikut diunggah bila Composer tidak tersedia di hosting.

Jika hasil **[CEK]** hosting tidak cocok dengan Laravel, stack ini diganti, tetapi aturan bisnis di dokumen ini tidak berubah.

## 4. Peran dan Hak Akses

Dua peran. Setiap peran mendapat **fitur inti** (yang diminta Irma) dan **fitur umum LMS** (yang lazim ada di LMS, sudah **disetujui Irma**). Keduanya sama-sama bagian dari LMS dan wajib dibangun; pembagian fase di bawah hanya soal urutan pengerjaan.

### Siswa (pengguna umum)

Inti:
- Daftar sendiri, masuk, keluar.
- Belajar sesuai urutan: buka materi, tonton video, kerjakan kuis, pretest, dan posttest sesuai aturan bagian 6–8.
- Melihat nilainya sendiri.

Umum LMS (disetujui Irma):
- **Beranda** berisi progres belajar (persentase bab selesai), tombol "Lanjutkan belajar" ke materi terakhir yang dibuka, ringkasan nilai, dan pengumuman terbaru.
- **Profil**: lihat dan ubah nama lengkap, username, email; ganti password.
- **Tandai materi selesai** dan lihat status tiap bab (terkunci, terbuka, selesai).
- **Riwayat nilai**: nilai pretest, tiap kuis bab, posttest, sisa kesempatan, dan status esai.
- **Pengumuman** dari admin.
- **Notifikasi dalam aplikasi** (mis. esai sudah dinilai, bab baru terbuka).
- **Pencarian** bab/materi.
- Siswa tidak bisa melihat data siswa lain.

### Admin (Irma)

Inti:
- Kelola bab, materi, video, kuis, dan soal.
- Menilai esai.
- Melihat laporan akses dan mengekspor ke Excel.

Umum LMS (disetujui Irma):
- **Dasbor ringkasan**: jumlah siswa, siswa aktif hari ini, rata-rata nilai per kuis, jumlah esai menunggu penilaian, aktivitas terbaru.
- **Kelola pengguna**: tambah manual, **impor dari Excel**, ubah data, nonaktifkan/aktifkan, hapus, reset password, reset kesempatan kuis, cari akun ganda lalu hapus atau gabung, lihat progres per siswa.
- **Status draf/terbit** untuk bab dan kuis, serta **pratinjau sebagai siswa**.
- **Impor soal dari Excel** (opsional) ke bank soal.
- **Umpan balik esai**: komentar singkat bersama nilai.
- **Pengumuman**: buat, ubah, hapus.
- **Laporan nilai** per siswa dan per kuis, dengan ekspor Excel.
- **Pengaturan** yang bisa diubah tanpa mengubah kode: nama situs, logo, nilai lulus (default 65), waktu per soal (default 60 detik), jumlah kesempatan (default 2).

### Urutan pengerjaan **[ASUMSI]**
- **Fase 1 (fitur inti dari permintaan Irma):** akun, struktur bab, materi dan video, kuis tiga tipe dengan aturan nilai, buka akses berurutan, log akses, laporan web dan Excel, penilaian esai, kelola siswa dasar.
- **Fase 2 (fitur umum LMS):** semua item "Umum LMS" di atas, dikerjakan setelah fase 1 stabil. Semuanya tetap wajib selesai.

## 5. Akun dan Pendaftaran

- Siswa mendaftar sendiri dengan **username dan email** (Irma menyatakan cukup itu), plus password.
- Username dan email wajib unik.
- **Nama lengkap wajib diisi** saat daftar, karena laporan akses mencatat **nama siswa**. Selain itu nama membantu admin menemukan akun ganda.
- Kolom **kelas** bersifat **opsional** (isian bebas, mis. "X-A" atau "XI MIPA 1"). Disetujui.
- Akun ganda tidak bisa dicegah sepenuhnya. Admin punya halaman daftar siswa dengan pencarian, serta aksi hapus akun dan reset.
- Lupa password: admin dapat mereset password siswa. **[ASUMSI]** Reset lewat email bersifat opsional dan hanya bila SMTP hosting tersedia.
- Admin dibuat lewat seeder atau perintah artisan, bukan lewat pendaftaran publik.

## 6. Struktur Konten dan Urutan Belajar

Urutan tetap:

1. **Pretest** (sebelum Bab 1)
2. **Bab 1 … Bab 5** — tiap bab berisi materi (slide), video YouTube, dan kuis bab
3. **Posttest** (setelah Bab 5 selesai)

Jumlah bab awal 5 (permintaan Irma), tetapi **dapat ditambah/dikurangi admin**. Jangan di-hardcode.

Aturan buka akses:

- Pretest terbuka saat siswa pertama masuk. Pretest tidak harus lulus (hanya diagnosis awal) dan hanya boleh dikerjakan **sekali**.
- Bab 1 terbuka setelah pretest dikerjakan. **[ASUMSI]** Siswa harus menyelesaikan pretest dulu sebelum masuk Bab 1.
- Bab berikutnya terbuka bila kuis bab sebelumnya **lulus** ATAU kedua kesempatan sudah terpakai (lihat bagian 7).
- Posttest terbuka setelah kuis Bab 5 selesai (lulus atau kesempatan habis). Hanya **sekali** dikerjakan.
- Materi di dalam bab bisa dibuka bebas setelah bab itu terbuka.

## 7. Kuis

### 7.1 Tipe soal

Pilihan ganda, isian, dan esai. Satu kuis bisa berisi 2 atau 3 tipe sekaligus. Admin memilih tipe saat membuat soal.

### 7.2 Aturan pengerjaan

- Soal **diacak** urutannya setiap percobaan. **Soal esai selalu di nomor-nomor terakhir** (disetujui).
- Waktu **1 menit per soal** hanya untuk **pilihan ganda dan isian**. Jika waktu habis, jawaban otomatis terkirim (kosong jika belum diisi).
- **Esai tidak punya batas waktu.**
- Satu soal ditampilkan per halaman. Timer divalidasi di **server** (waktu mulai soal disimpan di server, toleransi beberapa detik), bukan hanya di JavaScript.
- Kuis bab: maksimal **2 kesempatan**. Nilai yang dipakai adalah yang **tertinggi** dari dua percobaan.
- **[ASUMSI]** Percobaan dihitung sejak siswa memulai. Menutup tab atau refresh tidak mengembalikan kesempatan; soal yang belum dijawab dihitung kosong.
- Pretest dan posttest: **sekali** dikerjakan.
- Siswa **boleh melihat nilainya**.
- **[ASUMSI]** Kunci jawaban tidak ditampilkan ke siswa.

### 7.3 Perhitungan nilai

- Setiap soal punya **bobot poin yang diatur admin, default 1 poin per soal** (disetujui). Nilai (0–100) = poin diperoleh ÷ total poin × 100.
- Pilihan ganda dinilai otomatis. **[ASUMSI]** Isian dinilai otomatis dengan pencocokan tanpa memperhatikan huruf besar/kecil dan spasi di tepi, dan admin bisa mengisi beberapa jawaban benar per soal.
- Esai dinilai **manual oleh Irma** lewat halaman admin (Irma menginput nilainya).
- **Kelulusan (nilai minimal 65) dihitung dari soal yang dinilai otomatis saja** (pilihan ganda dan isian), supaya siswa tidak menunggu penilaian esai. Irma sudah menyetujui aturan ini.
- Nilai esai ditambahkan setelah dinilai, lalu **nilai akhir diperbarui** dan tampil di laporan.
- Jika sebuah kuis hanya berisi esai, kuis itu tidak mengunci bab berikutnya.

### 7.4 Status hasil kuis

Setiap percobaan memiliki status yang tampil jelas bagi siswa dan admin:

- `lulus` — nilai otomatis ≥ 65
- `belum lulus, lanjut` — dua kesempatan habis, tetap boleh lanjut
- `belum lulus` — masih ada kesempatan
- `menunggu penilaian esai` — ada esai yang belum dinilai

Admin dapat **mereset kesempatan** kuis seorang siswa.

## 8. Materi dan Video

- **Video:** link YouTube yang diputar langsung di halaman (embed). Tidak ada unggah video.
- **Materi:** Irma menyiapkan materi dalam format PPT, lalu **mengekspornya menjadi PDF** dan mengunggahnya lewat panel admin. PDF ditampilkan per halaman di web dengan **PDF.js** (keputusan final; opsi Google Slides tidak dipakai).
- Validasi unggahan: hanya PDF, dengan batas ukuran yang wajar (karena storage hosting 2 GB).
- Panel admin menampilkan petunjuk singkat cara mengekspor PPT ke PDF.

## 9. Log Akses dan Laporan

Yang dicatat:

- **Nama siswa** yang mengakses
- **Waktu akses** (waktu login)
- Waktu aktivitas terakhir (dipakai untuk menghitung durasi)
- **Berapa lama mengakses** = durasi kunjungan per sesi login (total per sesi; tidak perlu per materi atau per video)

Cara mencatat durasi: JavaScript mengirim sinyal ringan (heartbeat) ke server tiap sekitar 30–60 detik selama halaman aktif; durasi dihitung dari login sampai aktivitas terakhir. Sesi dianggap berakhir bila logout atau tidak ada aktivitas melewati batas waktu tertentu.

Laporan akses:

- Kolom: **nama siswa, waktu akses, durasi**.
- Ditampilkan sebagai tabel di web **dan** bisa diunduh ke **Excel**.
- Filter berdasarkan siswa dan rentang tanggal. Tidak ada filter kelompok eksperimen/kontrol (dikonfirmasi tidak ada).
- Ada juga **halaman Laporan Nilai Siswa di web admin** (per siswa: nilai pretest, kuis tiap bab, posttest) **dan tombol unduh Excel** (disetujui).

Izin penelitian dan persetujuan sekolah terkait pengumpulan data sudah diurus Irma.

## 10. Halaman Admin

Beranda/dasbor, Pengguna, Bab (dengan materi dan video), Kuis dan Bank Soal, Penilaian Esai, Pengumuman, Laporan (akses dan nilai), Pengaturan. Rincian kemampuan ada di bagian 4.

## 11. Halaman Siswa

Daftar/Masuk, Beranda (progres dan pengumuman), Daftar Bab, Halaman Bab (materi dan video), Kuis (satu soal per layar dengan timer), Nilai Saya, Pengumuman, Profil. Rincian kemampuan ada di bagian 4.

## 12. Non-Fungsional

- Responsif, diprioritaskan **mobile-first**; nyaman dipakai di HP.
- Mampu melayani sekitar 100 siswa; query harus diberi indeks dan N+1 dihindari.
- Keamanan: password di-hash, proteksi CSRF, validasi semua input, otorisasi per peran (policy/middleware), batas percobaan login, unggahan hanya file yang diizinkan.
- Jawaban kuis dan aturan nilai dihitung di server; jangan percaya data dari klien.
- Zona waktu: **[ASUMSI]** Asia/Jakarta (WIB), dan semua waktu di laporan ditampilkan dalam WIB.
- Data siswa adalah data penelitian, jadi hanya admin yang dapat melihat dan mengekspor.
- Kumpulkan data seminimal mungkin (pengguna adalah siswa SMA, sebagian mungkin di bawah 18 tahun): tanpa foto, nomor telepon, atau data lain di luar yang dibutuhkan.

## 13. Di Luar Lingkup (saat ini)

Peran guru, forum/diskusi dan chat, sertifikat, kalender/penjadwalan, notifikasi email, unggah video, aplikasi mobile native, pembayaran. (Forum diskusi biasanya ada di LMS; masukkan hanya jika Irma meminta.)

## 14. Pertanyaan Terbuka

1. **[CEK] Hosting:** versi PHP, MySQL/MariaDB, akses SSH/Composer, batas memori. Hosting dan domain diurus belakangan.
2. Deadline, jadwal uji coba, budget dan sistem pembayaran, batas revisi (di luar spesifikasi teknis, tetapi perlu tertulis).

Sudah diputuskan: materi memakai PDF dan PDF.js, kolom kelas opsional, bobot poin default 1 dan esai di nomor terakhir, laporan nilai di web plus Excel.
