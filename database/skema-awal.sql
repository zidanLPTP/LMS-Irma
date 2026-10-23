-- ============================================================
-- Skema Awal Basis Data LMS BioSTEMX (Cadangan Impor Manual)
-- Tanggal Ekspor: 2026-10-23 22:28:07
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Struktur Tabel `bab`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `bab`;
CREATE TABLE `bab` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `urutan` int(10) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status_terbit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bab_urutan_unique` (`urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `cache`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `cache_locks`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `failed_jobs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `jawaban_siswa`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `jawaban_siswa`;
CREATE TABLE `jawaban_siswa` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `percobaan_kuis_id` bigint(20) unsigned NOT NULL,
  `soal_id` bigint(20) unsigned NOT NULL,
  `pilihan_jawaban_id` bigint(20) unsigned DEFAULT NULL,
  `jawaban_teks` text DEFAULT NULL,
  `waktu_mulai_soal` datetime NOT NULL,
  `waktu_jawab` datetime DEFAULT NULL,
  `poin_diperoleh` decimal(5,2) NOT NULL DEFAULT 0.00,
  `catatan_guru` text DEFAULT NULL,
  `dinilai_pada` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jawaban_siswa_percobaan_kuis_id_soal_id_unique` (`percobaan_kuis_id`,`soal_id`),
  KEY `jawaban_siswa_soal_id_foreign` (`soal_id`),
  KEY `jawaban_siswa_pilihan_jawaban_id_foreign` (`pilihan_jawaban_id`),
  CONSTRAINT `jawaban_siswa_percobaan_kuis_id_foreign` FOREIGN KEY (`percobaan_kuis_id`) REFERENCES `percobaan_kuis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jawaban_siswa_pilihan_jawaban_id_foreign` FOREIGN KEY (`pilihan_jawaban_id`) REFERENCES `pilihan_jawaban` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jawaban_siswa_soal_id_foreign` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `job_batches`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `jobs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `kuis`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `kuis`;
CREATE TABLE `kuis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bab_id` bigint(20) unsigned DEFAULT NULL,
  `tipe_kuis` enum('pretest','kuis_bab','posttest') NOT NULL,
  `judul` varchar(255) NOT NULL,
  `batas_waktu_per_soal` int(10) unsigned DEFAULT NULL,
  `nilai_lulus` decimal(5,2) DEFAULT NULL,
  `maksimal_kesempatan` int(10) unsigned DEFAULT NULL,
  `acak_soal` tinyint(1) NOT NULL DEFAULT 1,
  `tipe_tunggal` varchar(255) GENERATED ALWAYS AS (case when `tipe_kuis` in ('pretest','posttest') then `tipe_kuis` else NULL end) VIRTUAL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kuis_tipe_tunggal_unique` (`tipe_tunggal`),
  KEY `kuis_bab_id_foreign` (`bab_id`),
  CONSTRAINT `kuis_bab_id_foreign` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `log_akses`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `log_akses`;
CREATE TABLE `log_akses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `aktivitas_terakhir` datetime NOT NULL,
  `durasi_detik` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_akses_user_id_waktu_masuk_index` (`user_id`,`waktu_masuk`),
  CONSTRAINT `log_akses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `materi`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `materi`;
CREATE TABLE `materi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bab_id` bigint(20) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `tipe` enum('pdf','youtube') NOT NULL,
  `berkas_pdf` varchar(255) DEFAULT NULL,
  `url_youtube` varchar(255) DEFAULT NULL,
  `urutan` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `materi_bab_id_foreign` (`bab_id`),
  CONSTRAINT `materi_bab_id_foreign` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `notifikasi`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `referensi` varchar(100) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `tautan` varchar(255) DEFAULT NULL,
  `dibaca_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notifikasi_user_id_jenis_referensi_unique` (`user_id`,`jenis`,`referensi`),
  CONSTRAINT `notifikasi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `password_reset_tokens`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `pengaturan`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kunci` varchar(255) NOT NULL,
  `nilai` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengaturan_kunci_unique` (`kunci`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `pengumuman`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pengumuman`;
CREATE TABLE `pengumuman` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `status` enum('draf','terbit') NOT NULL DEFAULT 'draf',
  `disematkan` tinyint(1) NOT NULL DEFAULT 0,
  `tanggal_kedaluwarsa` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pengumuman_admin_id_foreign` (`admin_id`),
  CONSTRAINT `pengumuman_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `pengumuman_dibaca`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pengumuman_dibaca`;
CREATE TABLE `pengumuman_dibaca` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `pengumuman_id` bigint(20) unsigned NOT NULL,
  `dibaca_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengumuman_dibaca_user_id_pengumuman_id_unique` (`user_id`,`pengumuman_id`),
  KEY `pengumuman_dibaca_pengumuman_id_foreign` (`pengumuman_id`),
  CONSTRAINT `pengumuman_dibaca_pengumuman_id_foreign` FOREIGN KEY (`pengumuman_id`) REFERENCES `pengumuman` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pengumuman_dibaca_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `percobaan_kuis`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `percobaan_kuis`;
CREATE TABLE `percobaan_kuis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kuis_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `nomor_percobaan` int(10) unsigned NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `status_pengerjaan` enum('sedang_mengerjakan','selesai') NOT NULL DEFAULT 'sedang_mengerjakan',
  `status_kelulusan` enum('lulus','belum_lulus','belum_lulus_lanjut') DEFAULT NULL,
  `esai_menunggu_nilai` tinyint(1) NOT NULL DEFAULT 0,
  `dibatalkan` tinyint(1) NOT NULL DEFAULT 0,
  `dibatalkan_oleh` bigint(20) unsigned DEFAULT NULL,
  `dibatalkan_pada` datetime DEFAULT NULL,
  `alasan_pembatalan` text DEFAULT NULL,
  `nilai_otomatis` decimal(5,2) DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `urutan_soal_teracak` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`urutan_soal_teracak`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `percobaan_kuis_kuis_id_user_id_nomor_percobaan_unique` (`kuis_id`,`user_id`,`nomor_percobaan`),
  KEY `percobaan_kuis_user_id_foreign` (`user_id`),
  KEY `percobaan_kuis_dibatalkan_oleh_foreign` (`dibatalkan_oleh`),
  CONSTRAINT `percobaan_kuis_dibatalkan_oleh_foreign` FOREIGN KEY (`dibatalkan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `percobaan_kuis_kuis_id_foreign` FOREIGN KEY (`kuis_id`) REFERENCES `kuis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `percobaan_kuis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `pilihan_jawaban`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pilihan_jawaban`;
CREATE TABLE `pilihan_jawaban` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `soal_id` bigint(20) unsigned NOT NULL,
  `teks_pilihan` text NOT NULL,
  `apakah_benar` tinyint(1) NOT NULL DEFAULT 0,
  `urutan` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pilihan_jawaban_soal_id_foreign` (`soal_id`),
  CONSTRAINT `pilihan_jawaban_soal_id_foreign` FOREIGN KEY (`soal_id`) REFERENCES `soal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `progres_materi`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `progres_materi`;
CREATE TABLE `progres_materi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `materi_id` bigint(20) unsigned NOT NULL,
  `sudah_dibuka` tinyint(1) NOT NULL DEFAULT 1,
  `waktu_buka` timestamp NOT NULL DEFAULT current_timestamp(),
  `selesai_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `progres_materi_user_id_materi_id_unique` (`user_id`,`materi_id`),
  KEY `progres_materi_materi_id_foreign` (`materi_id`),
  CONSTRAINT `progres_materi_materi_id_foreign` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `progres_materi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `riwayat_nilai_esai`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_nilai_esai`;
CREATE TABLE `riwayat_nilai_esai` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `jawaban_siswa_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `poin_lama` decimal(5,2) DEFAULT NULL,
  `poin_baru` decimal(5,2) NOT NULL,
  `catatan_lama` text DEFAULT NULL,
  `catatan_baru` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `riwayat_nilai_esai_jawaban_siswa_id_foreign` (`jawaban_siswa_id`),
  KEY `riwayat_nilai_esai_admin_id_foreign` (`admin_id`),
  CONSTRAINT `riwayat_nilai_esai_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `riwayat_nilai_esai_jawaban_siswa_id_foreign` FOREIGN KEY (`jawaban_siswa_id`) REFERENCES `jawaban_siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `riwayat_pengaturan`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_pengaturan`;
CREATE TABLE `riwayat_pengaturan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kunci` varchar(255) NOT NULL,
  `nilai_lama` text DEFAULT NULL,
  `nilai_baru` text DEFAULT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `riwayat_pengaturan_admin_id_foreign` (`admin_id`),
  CONSTRAINT `riwayat_pengaturan_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `riwayat_profil`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_profil`;
CREATE TABLE `riwayat_profil` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `kolom` varchar(255) NOT NULL,
  `nilai_lama` text DEFAULT NULL,
  `nilai_baru` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `riwayat_profil_user_id_foreign` (`user_id`),
  CONSTRAINT `riwayat_profil_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `riwayat_tindakan_admin`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_tindakan_admin`;
CREATE TABLE `riwayat_tindakan_admin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `siswa_id` bigint(20) unsigned DEFAULT NULL,
  `jenis` enum('reset_password','reset_kesempatan','nonaktifkan','aktifkan','ubah_data','hapus','impor_siswa') NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `riwayat_tindakan_admin_admin_id_foreign` (`admin_id`),
  KEY `riwayat_tindakan_admin_siswa_id_foreign` (`siswa_id`),
  CONSTRAINT `riwayat_tindakan_admin_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `riwayat_tindakan_admin_siswa_id_foreign` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `sessions`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `soal`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `soal`;
CREATE TABLE `soal` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kuis_id` bigint(20) unsigned NOT NULL,
  `tipe_soal` enum('pilihan_ganda','isian','esai') NOT NULL,
  `pertanyaan` text NOT NULL,
  `bobot_poin` int(10) unsigned NOT NULL DEFAULT 1,
  `urutan` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `soal_kuis_id_foreign` (`kuis_id`),
  CONSTRAINT `soal_kuis_id_foreign` FOREIGN KEY (`kuis_id`) REFERENCES `kuis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Struktur Tabel `users`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `peran` enum('admin','siswa') NOT NULL DEFAULT 'siswa',
  `kelas` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `wajib_ganti_password` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
