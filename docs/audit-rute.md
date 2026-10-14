# Audit Rute BioSTEMX

Dokumen ini memetakan seluruh rute HTTP yang terdaftar pada sistem LMS BioSTEMX (`Route::getRoutes()`), middleware yang diterapkan, serta aturan hak akses perannya.

## Prinsip Otorisasi Rute
1. **Daftar Putih Tamu (Guest Only):** Hanya rute `/masuk` dan `/daftar` (serta halaman galat publik) yang terbuka untuk pengguna belum terautentikasi.
2. **Area Admin (`/admin/*`):** Dilindungi middleware `auth` dan `peran:admin`. Akses oleh siswa menghasilkan respon `403 Forbidden`, sedangkan tamu dialihkan ke halaman login (`/masuk`).
3. **Area Siswa (`/siswa/*`):** Dilindungi middleware `auth`, `peran:siswa`, `periksa.status` (mengecek akun aktif dan keharusan ganti password), serta `catat.aktivitas` (heartbeat detak jantung).
4. **Header Keamanan Global:** Semua rute pada grup `web` menerapkan middleware `HeaderKeamanan` (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, dan CSP).

---

## Tabel Rute Sistem

| Metode | URI | Nama Rute | Middleware | Peran / Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | — | `web` | Tamu / Redirect ke `/masuk` |
| `GET` | `/masuk` | `masuk` | `web`, `guest` | Tamu (Belum login) |
| `POST` | `/masuk` | `masuk.kirim` | `web`, `guest` | Tamu (Belum login) |
| `GET` | `/daftar` | `daftar` | `web`, `guest` | Tamu (Belum login) |
| `POST` | `/daftar` | `daftar.kirim` | `web`, `guest`, `throttle:10,60` | Tamu (Dibatasi 10 req/jam/IP) |
| `POST` | `/keluar` | `keluar` | `web`, `auth` | Pengguna Terautentikasi (Siswa/Admin) |
| `GET` | `/materi/{materi}/pdf` | `materi.pdf` | `web`, `auth` | Siswa (Bab terbuka & terbit) / Admin |
| `GET` | `/siswa/beranda` | `siswa.beranda` | `web`, `auth`, `peran:siswa`, `periksa.status`, `catat.aktivitas` | Siswa Aktif |
| `GET` | `/siswa/cari` | `siswa.cari` | `web`, `auth`, `peran:siswa`, `throttle:30,1` | Siswa Aktif |
| `GET` | `/siswa/bab/{bab}` | `siswa.bab.show` | `web`, `auth`, `peran:siswa`, `periksa.status`, `catat.aktivitas` | Siswa Aktif (Bab Terbuka) |
| `GET` | `/siswa/bab/{bab}/materi/{materi}` | `siswa.bab.materi.show` | `web`, `auth`, `peran:siswa`, `periksa.status`, `catat.aktivitas` | Siswa Aktif (Bab Terbuka) |
| `POST` | `/siswa/bab/{bab}/materi/{materi}/selesai` | `siswa.bab.materi.toggle-selesai` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/kuis/{kuis}` | `siswa.kuis.show` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `POST` | `/siswa/kuis/{kuis}/mulai` | `siswa.kuis.mulai` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/percobaan/{percobaan}` | `siswa.kuis.kerjakan` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Percobaan |
| `POST` | `/siswa/percobaan/{percobaan}/soal/{soal}/mulai` | `siswa.kuis.mulai-soal` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Percobaan |
| `POST` | `/siswa/percobaan/{percobaan}/soal/{soal}/jawab` | `siswa.kuis.jawab` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Percobaan |
| `POST` | `/siswa/percobaan/{percobaan}/selesai` | `siswa.kuis.selesai` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Percobaan |
| `GET` | `/siswa/percobaan/{percobaan}/hasil` | `siswa.kuis.hasil` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Percobaan |
| `POST` | `/siswa/detak` | `siswa.detak` | `web`, `auth`, `peran:siswa`, `throttle:10,1` | Siswa Aktif (Heartbeat) |
| `GET` | `/siswa/nilai` | `siswa.nilai.index` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/notifikasi` | `siswa.notifikasi.index` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `POST` | `/siswa/notifikasi/baca-semua` | `siswa.notifikasi.baca-semua` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/notifikasi/{notifikasi}/baca` | `siswa.notifikasi.baca` | `web`, `auth`, `peran:siswa` | Siswa Pemilik Notifikasi |
| `GET` | `/siswa/pengumuman` | `siswa.pengumuman.index` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/pengumuman/{pengumuman}` | `siswa.pengumuman.show` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/profil` | `siswa.profil.edit` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `PUT` | `/siswa/profil` | `siswa.profil.update` | `web`, `auth`, `peran:siswa` | Siswa Aktif |
| `GET` | `/siswa/password` | `siswa.password.edit` | `web`, `auth`, `peran:siswa` | Siswa (Wajib/Sukarela) |
| `PUT` | `/siswa/password` | `siswa.password.update` | `web`, `auth`, `peran:siswa` | Siswa (Wajib/Sukarela) |
| `GET` | `/admin/beranda` | `admin.beranda` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET` | `/admin/pengaturan` | `admin.pengaturan.edit` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `PUT` | `/admin/pengaturan` | `admin.pengaturan.update` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `RESOURCE` | `/admin/pengumuman` | `admin.pengumuman.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `RESOURCE` | `/admin/bab` | `admin.bab.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `RESOURCE` | `/admin/bab/{bab}/materi` | `admin.bab.materi.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `RESOURCE` | `/admin/kuis` | `admin.kuis.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `RESOURCE` | `/admin/kuis/{kuis}/soal` | `admin.kuis.soal.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET/POST` | `/admin/penilaian-esai/*` | `admin.penilaian-esai.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET` | `/admin/laporan/akses` | `admin.laporan.akses` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET` | `/admin/laporan/akses/ekspor` | `admin.laporan.akses.ekspor` | `web`, `auth`, `peran:admin`, `throttle:15,1` | Khusus Admin |
| `GET` | `/admin/laporan/nilai` | `admin.laporan.nilai` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET` | `/admin/laporan/nilai/ekspor` | `admin.laporan.nilai.ekspor` | `web`, `auth`, `peran:admin`, `throttle:15,1` | Khusus Admin |
| `RESOURCE` | `/admin/pengguna` | `admin.pengguna.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
| `GET/POST` | `/admin/impor-siswa/*` | `admin.impor-siswa.*` | `web`, `auth`, `peran:admin` | Khusus Admin (Throttled) |
| `GET/POST` | `/admin/kuis/{kuis}/impor-soal/*` | `admin.impor-soal.*` | `web`, `auth`, `peran:admin` | Khusus Admin (Throttled) |
| `GET` | `/admin/pratinjau/*` | `admin.pratinjau.*` | `web`, `auth`, `peran:admin` | Khusus Admin |
