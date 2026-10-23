# Prosedur Uji Beban Staging & Panduan Operasional Hosting

Dokumen ini memuat panduan pengujian ketahanan beban di subdomain staging (`staging.<domain>`), cara memantau penggunaan sumber daya di cPanel, matriks kriteria keputusan, dan strategi operasional 100 siswa serentak.

---

## 1. Analisis Kebutuhan Lalu Lintas (Traffic Budget)

Untuk 100 siswa SMA yang belajar secara bersamaan di kelas/laboratorium:

| Aktivitas | Frekuensi per Siswa | Total Permintaan (100 Siswa) | Karakteristik Beban |
| :--- | :--- | :--- | :--- |
| **Sinyal Detak Jantung (`/detak`)** | 1 kali tiap 45 detik | **~2.2 req / detik** | Sangat ringan, respons 204 No Content, query update tunggal |
| **Pengerjaan Kuis** | 1 soal tiap 60 detik | **~1.6 req / detik** | Validasi waktu, penyimpanan jawaban |
| **Membaca Materi PDF / Video** | Sesi santai (10-15 menit) | Di-cache di browser | Ringan (video langsung embed YouTube CDN) |
| **Login Awal (Bcrypt CPU Burst)** | Bersamaan di menit ke-0 | **Lonjakan CPU tinggi** | Bcrypt cost 10 membutuhkan komputasi CPU intensif |

> [!IMPORTANT]
> **Total Throughput Normal**: Hanya berkisar **3.5 hingga 5 permintaan per detik** untuk 100 siswa. Angka ini sangat aman bagi shared hosting. Titik kritis satu-satunya adalah **lonjakan saat seluruh siswa login bersamaan dalam 1 menit pertama**.

---

## 2. Cara Memantau Sumber Daya di cPanel

Saat pengujian atau simulasi berlangsung:
1. Buka cPanel > kelompok **Metrics** > klik menu **Resource Usage** (atau **CPU and Concurrent Connection Usage**).
2. Klik tombol **[Details]** untuk melihat grafik riwayat penggunaan sumber daya:
   - **CPU Usage (%)**: Persentase kapasitas CPU yang dialokasikan akun Anda.
   - **Entry Processes (EP)**: Jumlah proses PHP/HTTP aktif yang sedang berjalan bersamaan (biasanya limit paket shared hosting: 20-30 EP).
   - **Physical Memory Usage (RAM)**: Pemakaian memori aktual (dari batas 2 GB).
   - **Faults (F)**: Jumlah permintaan yang **ditolak server** karena melampaui batas CPU/EP.

---

## 3. Matriks Keputusan Hasil Uji Beban

| Status | Indikator Sumber Daya cPanel | Kinerja Web Siswa | Keputusan & Tindakan |
| :--- | :--- | :--- | :--- |
| **HIJAU (Lulus)** | - Faults = 0<br>- Entry Process < 70% kuota<br>- CPU < 80% | Halaman terbuka cepat (< 1.5 detik), pengerjaan kuis lancar tanpa kendala. | **Rilis ke Domain Utama**: Konfigurasi server sudah sangat memadai untuk target 100 siswa. |
| **KUNING (Waspada)** | - Faults = 0<br>- Entry Process sempat 80-90% saat login massal | Login siswa memakan waktu 2-3 detik saat gelombang pertama, namun setelah masuk sistem kembali cepat. | **Terapkan Login 3 Gelombang**: Bagi login siswa per kelas/kelompok (selisih 5-10 menit). Tidak perlu naik paket. |
| **MERAH (Kelebihan Beban)** | - Faults > 0<br>- Muncul HTTP 508 (*Resource Limit Reached*) | Siswa menemui halaman galat limit hosting saat login bersamaan. | **Naikkan Paket / Garansi Refund**: Hubungi penyedia hosting untuk menambah alokasi Entry Process (EP), atau manfaatkan garansi pengembalian dana 30 hari jika limit terlalu kecil. |

---

## 4. Saran Operasional Hari-H Penelitian (Irma & Guru)

Untuk memastikan pengalaman belajar 100 siswa SMA tetap mulus tanpa hambatan:

1. **Jalankan Login Bertahap (2-3 Gelombang)**:
   - Siswa Kelas A (30-35 siswa) dipersilakan login terlebih dahulu pada menit 00.
   - Siswa Kelas B (30-35 siswa) login pada menit 07.
   - Siswa Kelas C (30-35 siswa) login pada menit 15.
   - *Manfaat*: Menghindari komputasi hashing password serempak 100 thread yang dapat memicu throttling CPU shared hosting.
2. **Kuis Berurutan Alami**:
   - Karena siswa memiliki kecepatan membaca materi yang berbeda-beda, saat masuk ke tahap Kuis Bab 1 hingga Bab 5, siswa akan tersebar secara alami sehingga beban server stabil di ~2-3 permintaan per detik.
3. **Ekspor Laporan Dilakukan Pasca-Sesi**:
   - Admin (Irma) disarankan melakukan ekspor Excel laporan nilai dan akses setelah sesi pembelajaran selesai agar tidak membebani memori PHP bersamaan dengan siswa yang sedang mengerjakan kuis.
