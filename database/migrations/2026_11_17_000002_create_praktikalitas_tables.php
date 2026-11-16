<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi tabel instrumen dan respon praktikalitas media.
     */
    public function up(): void
    {
        Schema::create('instrumen_praktikalitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->string('aspek', 100);
            $table->text('pernyataan');
            $table->timestamps();
        });

        Schema::create('respon_praktikalitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instrumen_id')->constrained('instrumen_praktikalitas')->cascadeOnDelete();
            $table->unsignedTinyInteger('skor'); // Skala Likert 1-4
            $table->timestamps();

            $table->unique(['user_id', 'instrumen_id']);
        });

        Schema::create('saran_praktikalitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('saran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('saran_praktikalitas');
        Schema::dropIfExists('respon_praktikalitas');
        Schema::dropIfExists('instrumen_praktikalitas');
    }
};
