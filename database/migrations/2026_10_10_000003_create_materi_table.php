<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel materi.
     */
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bab_id')->constrained('bab')->cascadeOnDelete();
            $table->string('judul');
            $table->enum('tipe', ['pdf', 'youtube']);
            $table->string('berkas_pdf')->nullable();
            $table->string('url_youtube')->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
