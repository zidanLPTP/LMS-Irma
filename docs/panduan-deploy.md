# Panduan Deploy Produksi LMS BioSTEMX (cPanel & SSH)

Panduan resmi deployment aplikasi **LMS BioSTEMX** ke shared hosting cPanel dengan spesifikasi:
- **Penyimpanan**: 2 GB SSD Storage
- **Fitur Server**: Akses SSH, Terminal cPanel, PHP Selector (PHP 8.4), Free SSL, Subdomain Tak Terbatas.
- **Sistem**: Laravel 13, MySQL/MariaDB, Blade + Tailwind CSS + Alpine.js.

---

## 1. Prinsip Utama Deployment Shared Hosting

1. **Jalur Utama adalah SSH**: Seluruh proses migrasi, optimasi cache, dan pembuatan admin dilakukan lewat SSH/Terminal server.
2. **Deploy Bertahap (Staging Dahulu)**: Deploy pertama kali dilakukan pada subdomain **`staging.<domain>`** untuk pengujian lingkup kecil dan verifikasi beban sebelum beralih ke domain utama.
3. **Build di Komputer Lokal**: Karena keterbatasan memori (RAM) shared hosting, jangan jalankan `composer install` penuh atau `npm run build` di server. Bangun aset di lokal, buat arsip rilis, lalu unggah ke server.
4. **Tanpa Daemon / Cron**: Sistem BioSTEMX dirancang hemat sumber daya dengan driver sesi & cache `database` / `file`, antrean `sync`, dan heartbeat detak jantung siswa berbasis request browser. Tidak memerlukan Cron Job atau Supervisor background process.

---

## 2. Persiapan Paket Rilis di Komputer Lokal

Jalankan perintah berikut di komputer lokal (terminal proyek):

```powershell
# 1. Pastikan seluruh pengujian logika lulus 100%
php artisan test

# 2. Bangun aset frontend produksi (Tailwind, SweetAlert2, PDF viewer)
npm run build

# 3. Optimasi dependensi Composer (hanya dependensi produksi, tanpa paket dev)
composer install --no-dev --optimize-autoloader

# 4. Buat arsip rilis zip (mengecualikan node_modules, .git, .env, tests)
Compress-Archive -Path app, bootstrap, config, database, lang, public, resources, routes, storage, vendor, artisan, composer.json, composer.lock -DestinationPath biostemx-release.zip -Force
```

> [!TIP]
> Setelah membuat paket rilis, Anda dapat mengembalikan paket pengembang di lokal kapan saja dengan:
> `composer install`

---

## 3. Konfigurasi Lingkungan cPanel

Sebelum mengunggah berkas, lakukan konfigurasi berikut di cPanel:

### A. Memilih Versi PHP 8.4 & Ekstensi
1. Buka cPanel > **Select PHP Version** (atau **PHP Selector**).
2. Pilih versi **PHP 8.4** dan klik *Set as current*.
3. Buka tab **Extensions**, pastikan ekstensi berikut tercentang aktif:
   - `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`, `intl`.

### B. Membuat Basis Data MySQL
1. Buka cPanel > **MySQL Databases**.
2. Buat database baru, contoh: `username_biostemx`.
3. Buat pengguna database baru, contoh: `username_lmsuser`, buat kata sandi kuat.
4. Tambahkan pengguna ke database dengan mencentang **ALL PRIVILEGES**.
5. Catat nama database, username, dan password tersebut.

### C. Menyiapkan Subdomain Staging
1. Buka cPanel > **Domains** > **Create A New Domain**.
2. Masukkan domain: `staging.namadomainanda.com`.
3. Hapus centang *Share document root*, lalu atur jalur Document Root ke:
   ```text
   /home/username/lms-staging/public
   ```
   *(PENTING: Seluruh berkas aplikasi diletakkan di luar folder `public_html` demi keamanan)*.

---

## 4. Langkah Deploy di Subdomain Staging (`staging.<domain>`)

### A. Unggah & Ekstrak Berkas
1. Masuk ke cPanel > **File Manager** (atau gunakan `scp` / SFTP via SSH).
2. Buat folder baru di root pengguna: `/home/username/lms-staging/`.
3. Unggah berkas `biostemx-release.zip` ke dalam folder tersebut, lalu klik **Extract**.

### B. Konfigurasi Berkas `.env` Produksi
1. Salin berkas `.env.example` menjadi `.env` di dalam folder `/home/username/lms-staging/`.
2. Sesuaikan konfigurasi kritis berikut:
   ```ini
   APP_NAME=BioSTEMX
   APP_ENV=production
   APP_KEY=  # Diisi otomatis pada langkah artisan key:generate
   APP_DEBUG=false
   APP_URL=https://staging.namadomainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_biostemx
   DB_USERNAME=username_lmsuser
   DB_PASSWORD=KataSandiDatabaseAnda

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   ```

### C. Eksekusi Perintah Server via SSH
Buka terminal SSH (menggunakan PuTTY, Git Bash, atau Terminal bawaan cPanel):

```bash
# 1. Pindah ke direktori aplikasi staging
cd ~/lms-staging

# 2. Generate Application Key jika belum ada
php artisan key:generate --force

# 3. Jalankan migrasi basis data dan seeder default pengaturan & admin
php artisan migrate --force --seed

# 4. Buat akun administrator baru secara aman (interaktif & password tersembunyi)
php artisan lms:buat-admin

# 5. Lakukan caching konfigurasi dan rute untuk performa maksimal
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Jalankan diagnosa otomatis kesiapan server
php artisan lms:cek-lingkungan

# 7. Pastikan perizinan folder penyimpanan aman
chmod -R 775 storage bootstrap/cache
```

### D. Pasang SSL Gratis
1. Buka cPanel > **SSL/TLS Status**.
2. Cari domain `staging.namadomainanda.com`, centang lalu klik **Run AutoSSL**.
3. Tunggu hingga status SSL aktif berwarna hijau (valid HTTPS).

---

## 5. Rilis ke Domain Utama (`namadomainanda.com`)

Setelah pengujian di staging selesai dan disetujui, terapkan ke domain utama dengan memilih salah satu metode:

### Opsi A: Metode Symlink (Rekomendasi & Paling Bersih)
1. Unggah aplikasi produksi ke folder terpisah: `/home/username/lms/`.
2. Konfigurasikan `.env` domain utama (`APP_URL=https://namadomainanda.com`).
3. Kosongkan isi `public_html` bawaan cPanel (backup jika ada berkas lama).
4. Lewat SSH, hubungkan `public_html` ke folder `public` aplikasi:
   ```bash
   cd ~
   rm -rf public_html
   ln -s /home/username/lms/public public_html
   ```

### Opsi B: Metode Pemisahan Dua Folder
Jika hosting tidak mengizinkan penghapusan/symlink pada `public_html`:
1. Simpan inti aplikasi di `/home/username/lms_core/`.
2. Pindahkan seluruh isi folder `lms_core/public/` ke dalam `/home/username/public_html/`.
3. Buka `/home/username/public_html/index.php`, ubah jalur require:
   ```php
   require __DIR__.'/../lms_core/vendor/autoload.php';
   $app = require_once __DIR__.'/../lms_core/bootstrap/app.php';
   ```

---

## 6. Jalur Cadangan Tanpa SSH (Bila Mengalami Kendala Akses)

Jika suatu saat akses terminal SSH tidak dapat dijangkau:
1. **Skema Database**: Buka phpMyAdmin > pilih database > tab **Import** > unggah berkas `database/skema-awal.sql`.
2. **Akun Administrator**:
   Jalankan di komputer lokal:
   ```bash
   php artisan lms:cetak-sql-admin --nama="Irma Pengelola" --username="admin_irma" --email="admin@sekolah.sch.id"
   ```
   Salin baris kueri `INSERT INTO users ...` yang dihasilkan, lalu jalankan di tab **SQL** phpMyAdmin hosting Anda.
