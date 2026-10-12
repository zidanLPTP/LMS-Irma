<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('kunci');
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru')->nullable();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengaturan');
    }
};
