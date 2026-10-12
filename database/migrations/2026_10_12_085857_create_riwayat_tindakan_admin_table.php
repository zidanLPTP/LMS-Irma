<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_tindakan_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('jenis', [
                'reset_password',
                'reset_kesempatan',
                'nonaktifkan',
                'aktifkan',
                'ubah_data',
                'hapus',
            ]);
            $table->text('keterangan');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_tindakan_admin');
    }
};
