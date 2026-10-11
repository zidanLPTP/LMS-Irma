<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel log_akses.
     */
    public function up(): void
    {
        Schema::create('log_akses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('waktu_masuk');
            $table->dateTime('aktivitas_terakhir');
            $table->unsignedInteger('durasi_detik')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'waktu_masuk']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_akses');
    }
};
