<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel notifikasi dalam aplikasi untuk siswa.
     */
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('jenis', 50); // esai_dinilai, langkah_terbuka, kesempatan_direset
            $table->string('referensi', 100); // untuk mencegah duplikasi mis. percobaan_1, bab_2, reset_kuis_3
            $table->string('judul');
            $table->text('isi');
            $table->string('tautan')->nullable();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            // Batasan unik (user_id, jenis, referensi) agar tidak ganda
            $table->unique(['user_id', 'jenis', 'referensi']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
