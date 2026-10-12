<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel pengumuman dan riwayat pembacaan siswa.
     */
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('isi');
            $table->enum('status', ['draf', 'terbit'])->default('draf');
            $table->boolean('disematkan')->default(false);
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->timestamps();
        });

        Schema::create('pengumuman_dibaca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pengumuman_id')->constrained('pengumuman')->cascadeOnDelete();
            $table->timestamp('dibaca_pada');
            $table->timestamps();

            $table->unique(['user_id', 'pengumuman_id']);
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_dibaca');
        Schema::dropIfExists('pengumuman');
    }
};
