<?php

return [
    'judul_halaman' => 'Pengaturan Sistem',
    'deskripsi_halaman' => 'Kelola konfigurasi umum LMS, nama situs, logo, batas nilai kelulusan, dan batas waktu kuis.',

    'nama_situs' => 'Nama Situs / LMS',
    'logo_situs' => 'Logo Situs',
    'logo_keterangan' => 'Format file: PNG, JPG, atau WEBP. Maksimal 1 MB. Tanpa logo, sistem menampilkan lambang daun.',
    'nilai_lulus' => 'Nilai Kelulusan Default (0 - 100)',
    'nilai_lulus_keterangan' => 'Nilai minimum untuk menyelesaikan kuis bab. Bawaan: 65.',
    'batas_waktu_per_soal' => 'Waktu per Soal (Detik)',
    'batas_waktu_keterangan' => 'Batas waktu pengerjaan soal pilihan ganda dan isian (10 - 600 detik). Bawaan: 60 detik.',
    'maksimal_kesempatan' => 'Jumlah Kesempatan Kuis Bab',
    'maksimal_kesempatan_keterangan' => 'Jumlah kesempatan pengerjaan kuis bab (1 - 5 kesempatan). Bawaan: 2. Pretest dan posttest selalu 1 kesempatan.',

    'peringatan_percobaan_ada' => 'Perhatian: Percobaan siswa sudah tercatat di sistem. Perubahan nilai kelulusan, batas waktu, atau kesempatan hanya berlaku untuk percobaan BARU. Status kelulusan percobaan lama yang telah selesai tidak akan dihitung ulang.',
    'keterangan_penimpa' => 'Kuis yang memiliki nilai pengaturan penimpa sendiri tidak akan terpengaruh oleh perubahan konfigurasi umum ini.',

    'riwayat' => [
        'judul' => 'Riwayat Perubahan Pengaturan',
        'kunci' => 'Parameter',
        'nilai_lama' => 'Nilai Lama',
        'nilai_baru' => 'Nilai Baru',
        'diubah_oleh' => 'Diubah Oleh',
        'waktu' => 'Waktu Perubahan',
        'kosong' => 'Belum ada riwayat perubahan pengaturan.',
    ],

    'pesan' => [
        'berhasil_disimpan' => 'Pengaturan sistem berhasil diperbarui.',
    ],
];
