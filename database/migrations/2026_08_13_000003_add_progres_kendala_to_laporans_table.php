<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Default 100: baris lama & laporan ad-hoc tanpa permintaan_laporan_id
            // secara semantik selalu laporan final/lengkap, bukan progress-log.
            $table->unsignedTinyInteger('progres')->default(100)->after('catatan');
            $table->text('kendala')->nullable()->after('progres');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn(['progres', 'kendala']);
        });
    }
};
