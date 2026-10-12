<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Perluas ENUM kolom jenis pada riwayat_tindakan_admin untuk mendukung 'impor_siswa'
        DB::statement("ALTER TABLE riwayat_tindakan_admin MODIFY COLUMN jenis ENUM(
            'reset_password',
            'reset_kesempatan',
            'nonaktifkan',
            'aktifkan',
            'ubah_data',
            'hapus',
            'impor_siswa'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE riwayat_tindakan_admin MODIFY COLUMN jenis ENUM(
            'reset_password',
            'reset_kesempatan',
            'nonaktifkan',
            'aktifkan',
            'ubah_data',
            'hapus'
        ) NOT NULL");
    }
};
