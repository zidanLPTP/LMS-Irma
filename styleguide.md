# STYLEGUIDE — LMS Penelitian Sekolah

Panduan tampilan agar konsisten. Target pengguna adalah **siswa SMA** (sekitar 15–18 tahun), jadi tampilannya modern dan bersih, tidak kekanak-kanakan dan tidak kaku seperti situs instansi. Irma meminta nuansa **biologi**: hijau muda, biru langit, warna lembut, dan bebas selama terasa segar. Semua teks antarmuka berbahasa Indonesia. Nilai di bawah ini adalah usulan awal dan boleh disesuaikan setelah melihat tampilan nyata.

## 1. Prinsip

1. **Lembut dan tenang.** Warna pastel sebagai latar, warna lebih tua hanya untuk teks dan tombol supaya mudah dibaca.
2. **Mobile-first.** Dirancang untuk layar HP dulu, lalu diperlebar untuk laptop.
3. **Jelas bagi siswa.** Status (terkunci, terbuka, selesai, lulus) selalu terlihat tanpa harus ditebak.
4. **Ringan.** Tanpa gambar besar dan animasi berat, karena dijalankan di shared hosting dan jaringan sekolah.
5. **Mudah dibaca.** Kontras teks memenuhi WCAG AA.
6. **Pantas untuk SMA.** Rapi dan terasa "aplikasi belajar masa kini": ruang lega, kartu sederhana, progres yang kelihatan. Tanpa maskot, tanpa tampilan ala SD.
7. **Palet ini yang berlaku.** Skill desain (mis. UI UX Pro Max) boleh dipakai untuk menyempurnakan tata letak dan interaksi, tetapi tidak boleh mengganti palet, font, atau aturan ikon di dokumen ini.

## 2. Warna

Definisikan sebagai variabel CSS dan konfigurasi Tailwind. Jangan menulis warna mentah di komponen.

### Hijau (utama)
| Token | Hex | Pemakaian |
|---|---|---|
| `green-50` | `#F0FAF4` | latar halaman, baris tabel selang-seling |
| `green-100` | `#DCF3E6` | latar kartu/aksen lembut |
| `green-300` | `#A8DDBF` | garis, ikon tidak aktif |
| `green-500` | `#5DB88A` | aksen, progres |
| `green-700` | `#2F7D5B` | **tombol utama**, tautan, judul |

### Biru langit (sekunder)
| Token | Hex | Pemakaian |
|---|---|---|
| `sky-50` | `#EFF8FD` | latar bagian sekunder |
| `sky-100` | `#D9EFFA` | kartu info, penanda video |
| `sky-300` | `#9ED3EE` | garis, aksen |
| `sky-500` | `#4FAFDD` | aksen, tautan sekunder |
| `sky-700` | `#1F6F9C` | tombol sekunder, ikon penting |

### Netral dan status
| Token | Hex | Pemakaian |
|---|---|---|
| `ink` | `#1F2D2A` | teks utama |
| `muted` | `#5B6B66` | teks sekunder |
| `border` | `#D6E4DD` | garis pemisah |
| `bg` | `#F7FBF9` | latar dasar |
| `success` | `#2F7D5B` | lulus |
| `warning` | `#B7791F` | menunggu penilaian, waktu hampir habis |
| `danger` | `#C0453F` | belum lulus, galat |
| `locked` | `#9AA9A3` | bab terkunci |

Aturan kontras: teks gelap (`ink`, `green-700`, `sky-700`) di atas latar pastel; teks putih hanya di atas `green-700` atau `sky-700`. Jangan menaruh teks `green-500` atau `sky-500` untuk isi paragraf.

## 3. Tipografi

- Keluarga huruf: **Nunito** (ramah dan bulat, cocok untuk belajar). Cadangan: `system-ui, sans-serif`.
- Boleh dimuat dari Google Fonts atau di-host sendiri (lebih cepat dan tidak bergantung jaringan luar).
- Ukuran dasar teks: 16px di HP (jangan lebih kecil).

| Gaya | Ukuran | Berat |
|---|---|---|
| Judul halaman (h1) | 28px / 32px (laptop) | 800 |
| Judul bagian (h2) | 22px | 700 |
| Subjudul (h3) | 18px | 700 |
| Teks isi | 16px, tinggi baris 1.6 | 400 |
| Teks kecil | 14px | 400 |

## 4. Tata Letak dan Jarak

- Skala jarak berkelipatan 4px (4, 8, 12, 16, 24, 32, 48).
- Lebar isi maksimum 1100px di laptop, rata tengah. Di HP satu kolom dengan jarak tepi 16px.
- Titik patah: HP < 640px, tablet 640–1024px, laptop > 1024px.
- Sudut membulat: kartu 16px, tombol dan input 12px.
- Bayangan sangat tipis (`0 1px 3px rgba(31,45,42,.08)`); hindari bayangan tebal.
- Area sentuh minimal 44×44px untuk tombol dan tautan penting.

## 5. Komponen

### Tombol
- **Utama:** latar `green-700`, teks putih; hover sedikit lebih gelap.
- **Sekunder:** latar `sky-700`, teks putih.
- **Garis:** bingkai `green-700`, teks `green-700`, latar transparan.
- **Bahaya:** latar `danger`, teks putih (hapus, reset). Tindakan merusak selalu minta konfirmasi.
- Tombol nonaktif: latar `locked`, tanpa efek hover.

### Kartu bab
Kartu dengan judul bab, ringkasan singkat, dan lencana status. Bab terkunci tampil redup dengan ikon gembok dan teks "Selesaikan kuis sebelumnya untuk membuka bab ini".

### Alur belajar (stepper)
Urutan **Pretest → Bab 1 … Bab 5 → Posttest** ditampilkan sebagai daftar vertikal di HP dan horizontal di laptop. Setiap langkah memiliki status:
- selesai (ikon centang hijau)
- terbuka (lingkaran hijau)
- terkunci (gembok abu-abu)

### Lencana status kuis
| Status | Warna | Teks |
|---|---|---|
| lulus | hijau | "Lulus" |
| belum lulus, lanjut | biru | "Belum lulus, lanjut" |
| belum lulus | merah | "Belum lulus" |
| menunggu penilaian esai | kuning | "Menunggu penilaian esai" |

### Halaman kuis
- Satu soal per layar, nomor soal dan sisa kesempatan jelas.
- Penghitung waktu berupa bilah yang menyusut; berubah `warning` pada 10 detik terakhir.
- Pilihan ganda berupa kartu besar yang mudah diketuk.
- Soal esai tanpa timer dan menampilkan kotak teks lebar.
- Setelah kirim, tampilkan nilai dan status.

### Formulir
Label di atas input, galat dalam teks merah di bawah input, tidak hanya warna (sertakan ikon atau teks). Placeholder bukan pengganti label.

### Tabel (laporan admin)
Header `green-100`, baris selang-seling `green-50`. Di HP tabel dapat digulir mendatar di dalam wadahnya (halaman tidak ikut bergeser). Tombol "Unduh Excel" di atas tabel, filter siswa dan tanggal di atasnya.

### Pemutar video
Video YouTube dalam bingkai 16:9, sudut membulat, latar `sky-100` sebagai penanda. Gunakan `loading="lazy"`.

### Penampil materi
Penampil per halaman (PDF.js atau embed) dengan tombol Sebelumnya/Berikutnya dan nomor halaman, lebar mengikuti wadah.

### Notifikasi
Pita di atas konten: sukses (hijau), info (biru), peringatan (kuning), galat (merah), disertai ikon dan teks.

## 6. Ikon dan Ornamen

### Aturan ikon
- Satu-satunya sumber ikon: **Lucide** (https://lucide.dev). Pakai SVG inline lewat komponen Blade `<x-ikon nama="..." />` yang membaca berkas SVG di `resources/svg/lucide/`. Cek nama ikon pada versi Lucide yang terpasang, karena beberapa nama pernah berganti.
- Satu ukuran dan satu ketebalan garis di seluruh situs (24px, `stroke-width` 2; 20px di tombol kecil). Warna mengikuti teks (`currentColor`).
- Ikon selalu **fungsional**: menemani label atau menunjukkan status. Bukan hiasan.
- Ikon yang hanya gambar harus punya `aria-label`; ikon pendamping label ditandai `aria-hidden="true"`.

### Yang dilarang (terlihat seperti template AI)
- Emoji di antarmuka.
- Ikon kilau/bintang (`sparkles`), tongkat sihir (`wand`), robot (`bot`), otak sirkuit, roket, dan sejenisnya.
- Gradasi ungu-biru, ikon dalam lingkaran gradasi, kartu fitur dengan ikon besar di atas judul tiga kolom yang seragam.
- Ilustrasi atau foto stok. Tidak ada gambar besar.

### Ikon yang disarankan (Lucide)
| Kebutuhan | Ikon |
|---|---|
| Beranda / dasbor | `house`, `layout-dashboard` |
| Bab / materi | `book-open`, `file-text` |
| Video | `circle-play` |
| Kuis | `clipboard-list` |
| Waktu | `clock`, `timer` |
| Nilai / laporan | `chart-column`, `file-spreadsheet` |
| Pengguna | `user`, `users` |
| Pengumuman / notifikasi | `megaphone`, `bell` |
| Pengaturan | `settings` |
| Terkunci / selesai / terbuka | `lock`, `circle-check`, `circle` |
| Aksi | `plus`, `pencil`, `trash-2`, `eye`, `download`, `search`, `log-in`, `log-out` |
| Navigasi | `chevron-left`, `chevron-right`, `menu`, `x` |
| Peringatan / info / galat | `triangle-alert`, `info`, `circle-x` |
| Logo | `leaf` (daun, ukuran kecil) |

### Nuansa biologi
Cukup lewat warna lembut, ikon `leaf` kecil di logo, dan bentuk sudut membulat. Tidak ada ornamen daun besar atau pola latar yang ramai.
- Tidak ada logo bawaan; sediakan penampung logo agar Irma bisa menambahkannya lewat Pengaturan.

## 7. Bahasa dan Nada Teks

Bahasa Indonesia yang ramah dan singkat, memakai "kamu" kepada siswa. Untuk anak SMA: santai tapi sopan, jelas, tidak menggurui, tanpa slang berlebihan, tanpa emoji, dan tanpa kalimat yang terlalu kekanak-kanakan.

| Situasi | Contoh |
|---|---|
| Bab terkunci | "Selesaikan kuis Bab 1 dulu untuk membuka bab ini." |
| Kesempatan tersisa | "Kamu punya 1 kesempatan lagi." |
| Waktu habis | "Waktu habis. Jawabanmu sudah terkirim." |
| Esai menunggu | "Jawaban esaimu menunggu penilaian dari guru." |
| Galat umum | "Ups, ada yang salah. Coba lagi sebentar lagi." |

Hindari istilah teknis di layar siswa.

## 8. Aksesibilitas

- Kontras AA, fokus keyboard terlihat, `alt` pada gambar bermakna.
- Informasi tidak hanya lewat warna (selalu sertai ikon atau teks).
- Elemen interaktif punya label yang dapat dibaca pembaca layar.
- Hormati `prefers-reduced-motion`; animasi seperlunya.

## 9. Penerapan Teknis

- Tailwind CSS: daftarkan token warna di konfigurasi Tailwind (blok `@theme` di CSS untuk Tailwind v4, atau `tailwind.config.js` untuk versi lama): `green`, `sky`, `ink`, dst.
- Variabel CSS di `:root` untuk token yang dipakai di luar Tailwind.
- Komponen Blade dipisah per elemen (`<x-tombol>`, `<x-kartu>`, `<x-lencana-status>`, `<x-langkah-belajar>`, `<x-pita-info>`, `<x-ikon>`). Nama komponen, kelas, dan variabel memakai Bahasa Indonesia sesuai `AGENTS.md`.
- Aset dibuild lokal (`npm run build`), hasilnya diunggah ke hosting.
