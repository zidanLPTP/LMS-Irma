<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel riwayat_nilai_esai.
     */
    public function up(): void
    {
        Schema::create('riwayat_nilai_esai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jawaban_siswa_id')->constrained('jawaban_siswa')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('poin_lama', 5, 2)->nullable();
            $table->decimal('poin_baru', 5, 2);
            $table->text('catatan_lama')->nullable();
            $table->text('catatan_baru')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_nilai_esai');
    }
};
