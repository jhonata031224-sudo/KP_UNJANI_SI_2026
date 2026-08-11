<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->foreignId('permintaan_laporan_id')
                ->nullable()
                ->after('tujuan_satuan_id')
                ->constrained('permintaan_laporans')
                ->nullOnDelete();
            $table->index('permintaan_laporan_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropForeign(['permintaan_laporan_id']);
            $table->dropIndex(['permintaan_laporan_id']);
            $table->dropColumn('permintaan_laporan_id');
        });
    }
};
