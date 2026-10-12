<?php

return [
    'judul_halaman' => 'Kelola Pengguna',
    'deskripsi_halaman' => 'Kelola akun siswa, pantau keaktifan, reset kata sandi, dan atur kesempatan kuis.',
    'tambah_siswa' => 'Tambah Siswa',
    'deteksi_ganda' => 'Pemeriksaan Akun Ganda',
    'cari_placeholder' => 'Cari nama, username, atau email...',
    'semua_kelas' => 'Semua Kelas',
    'semua_status' => 'Semua Status',
    'aktif' => 'Aktif',
    'nonaktif' => 'Nonaktif',
    'wajib_ganti_password' => 'Wajib Ganti Sandi',

    'tabel' => [
        'nama_lengkap' => 'Nama Siswa',
        'identitas' => 'Identitas Akun',
        'kelas' => 'Kelas',
        'status' => 'Status',
        'keaktifan' => 'Keaktifan',
        'progres' => 'Progres Belajar',
        'aksi' => 'Aksi',
        'jumlah_sesi' => ':jumlah sesi',
        'belum_pernah_masuk' => 'Belum pernah masuk',
        'langkah_selesai' => ':selesai dari :total langkah',
    ],

    'detail' => [
        'judul' => 'Detail Siswa: :nama',
        'informasi_akun' => 'Informasi Akun Siswa',
        'stepper_belajar' => 'Tahapan Pembelajaran',
        'riwayat_kuis' => 'Riwayat Percobaan Kuis',
        'sesi_terakhir' => '10 Sesi Akses Terakhir',
        'jejak_audit' => 'Jejak Tindakan Administrator',
        'reset_kesempatan' => 'Reset Kesempatan Kuis',
        'reset_password' => 'Reset Kata Sandi',
        'ubah_profil' => 'Ubah Data Siswa',
    ],

    'akun_ganda' => [
        'judul' => 'Pemeriksaan Akun Nama Ganda',
        'deskripsi' => 'Daftar nama lengkap siswa yang terdaftar lebih dari satu kali (tidak peka huruf besar-kecil). Akun tanpa data dapat dihapus untuk membersihkan pangkalan data.',
        'peringatan_gabung' => 'Fitur penggabungan otomatis data dua akun saat ini ditunda untuk menjaga keutuhan log akses penelitian.',
        'hapus_konfirmasi' => 'Apakah Anda yakin ingin menghapus akun ini secara permanen?',
        'tidak_ditemukan' => 'Tidak ditemukan akun dengan nama lengkap yang sama.',
    ],

    'pesan' => [
        'berhasil_tambah' => 'Siswa baru berhasil ditambahkan.',
        'berhasil_ubah' => 'Data siswa berhasil diperbarui.',
        'berhasil_nonaktif' => 'Akun siswa berhasil dinonaktifkan.',
        'berhasil_aktif' => 'Akun siswa berhasil diaktifkan kembali.',
        'berhasil_reset_password' => 'Kata sandi sementara berhasil dibuat. Berikan kata sandi ini kepada siswa.',
        'berhasil_reset_kesempatan' => 'Kesempatan kuis berhasil direset. Percobaan sebelumnya telah dibatalkan.',
        'berhasil_hapus' => 'Akun tanpa data berhasil dihapus.',
        'gagal_hapus_berdata' => 'Akun yang memiliki data aktivitas belajar tidak dapat dihapus. Anda hanya dapat menonaktifkannya.',
    ],
];
