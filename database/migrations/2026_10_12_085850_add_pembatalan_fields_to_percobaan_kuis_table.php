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
        Schema::table('percobaan_kuis', function (Blueprint $table) {
            $table->foreignId('dibatalkan_oleh')->nullable()->after('dibatalkan')->constrained('users')->nullOnDelete();
            $table->dateTime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
            $table->text('alasan_pembatalan')->nullable()->after('dibatalkan_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('percobaan_kuis', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropColumn(['dibatalkan_oleh', 'dibatalkan_pada', 'alasan_pembatalan']);
        });
    }
};
