<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi LMS Penelitian Sekolah
    |--------------------------------------------------------------------------
    */

    // Toleransi waktu pengiriman jawaban per soal (detik)
    'toleransi_timer_detik' => 5,

    // Batas interval jeda sinyal detak jantung sesi (detik)
    'batas_jeda_sesi_detik' => 120,

    // Jeda minimal antar pembaruan aktivitas untuk mencegah tulis berlebihan (detik)
    'jeda_minimal_tulis_detik' => 5,

    // Interval pengiriman sinyal detak jantung browser (detik)
    'interval_detak_detik' => 45,

    // Batas waktu tanpa interaksi (diam) sebelum menghentikan detak jantung (detik) - default 1800 (30 menit)
    'batas_diam_detik' => 1800,

    // Batas maksimal ukuran unggahan berkas PDF materi (KB) - default 20 MB (20480 KB)
    'batas_maksimal_pdf_kb' => 20480,
];
