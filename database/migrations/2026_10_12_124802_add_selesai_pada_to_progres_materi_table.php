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
        Schema::table('progres_materi', function (Blueprint $table) {
            $table->timestamp('selesai_pada')->nullable()->after('waktu_buka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progres_materi', function (Blueprint $table) {
            $table->dropColumn('selesai_pada');
        });
    }
};
