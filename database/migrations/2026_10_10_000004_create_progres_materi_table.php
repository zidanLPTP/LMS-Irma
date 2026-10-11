<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel progres_materi.
     */
    public function up(): void
    {
        Schema::create('progres_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $table->boolean('sudah_dibuka')->default(true);
            $table->timestamp('waktu_buka')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'materi_id']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('progres_materi');
    }
};
