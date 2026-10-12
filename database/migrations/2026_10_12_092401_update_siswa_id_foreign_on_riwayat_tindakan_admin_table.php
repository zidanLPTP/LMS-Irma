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
        Schema::table('riwayat_tindakan_admin', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->foreign('siswa_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_tindakan_admin', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->foreign('siswa_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
