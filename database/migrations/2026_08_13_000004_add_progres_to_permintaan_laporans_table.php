<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->unsignedTinyInteger('progres')->default(0)->after('status');
        });

        // Backfill: permintaan lama yang sudah punya laporan_id (sudah di tahap
        // Menunggu Pemeriksaan atau Selesai) semestinya progres 100%, bukan 0%.
        DB::table('permintaan_laporans')->whereNotNull('laporan_id')->update(['progres' => 100]);
    }

    public function down(): void
    {
        Schema::table('permintaan_laporans', function (Blueprint $table) {
            $table->dropColumn('progres');
        });
    }
};
