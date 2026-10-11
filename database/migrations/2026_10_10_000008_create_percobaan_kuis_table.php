<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel percobaan_kuis.
     */
    public function up(): void
    {
        Schema::create('percobaan_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('nomor_percobaan');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai')->nullable();
            $table->enum('status_pengerjaan', ['sedang_mengerjakan', 'selesai'])->default('sedang_mengerjakan');
            $table->enum('status_kelulusan', ['lulus', 'belum_lulus', 'belum_lulus_lanjut'])->nullable();
            $table->boolean('esai_menunggu_nilai')->default(false);
            $table->boolean('dibatalkan')->default(false);
            $table->decimal('nilai_otomatis', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->json('urutan_soal_teracak');
            $table->timestamps();

            $table->unique(['kuis_id', 'user_id', 'nomor_percobaan']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('percobaan_kuis');
    }
};
