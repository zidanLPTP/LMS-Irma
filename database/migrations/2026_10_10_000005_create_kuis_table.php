<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel kuis.
     */
    public function up(): void
    {
        Schema::create('kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bab_id')->nullable()->constrained('bab')->cascadeOnDelete();
            $table->enum('tipe_kuis', ['pretest', 'kuis_bab', 'posttest']);
            $table->string('judul');
            $table->unsignedInteger('batas_waktu_per_soal')->nullable();
            $table->decimal('nilai_lulus', 5, 2)->nullable();
            $table->unsignedInteger('maksimal_kesempatan')->nullable();
            $table->boolean('acak_soal')->default(true);
            // Virtual column yang hanya terisi 'pretest' atau 'posttest' agar dijamin unik di basis data
            $table->string('tipe_tunggal')->virtualAs(
                "CASE WHEN tipe_kuis IN ('pretest', 'posttest') THEN tipe_kuis ELSE NULL END"
            )->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('kuis');
    }
};
