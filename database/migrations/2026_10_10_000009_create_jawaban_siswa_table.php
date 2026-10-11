<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel jawaban_siswa.
     */
    public function up(): void
    {
        Schema::create('jawaban_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('percobaan_kuis_id')->constrained('percobaan_kuis')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->foreignId('pilihan_jawaban_id')->nullable()->constrained('pilihan_jawaban')->nullOnDelete();
            $table->text('jawaban_teks')->nullable();
            $table->dateTime('waktu_mulai_soal');
            $table->dateTime('waktu_jawab')->nullable();
            $table->decimal('poin_diperoleh', 5, 2)->default(0.00);
            $table->text('catatan_guru')->nullable();
            $table->dateTime('dinilai_pada')->nullable();
            $table->timestamps();

            $table->unique(['percobaan_kuis_id', 'soal_id']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswa');
    }
};
