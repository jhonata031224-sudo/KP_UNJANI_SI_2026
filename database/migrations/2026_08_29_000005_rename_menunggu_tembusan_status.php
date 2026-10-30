<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ganti label status "Menunggu Tembusan" jadi "Menunggu Balasan" pada
 * laporan_kendalas yang sudah kadung tersimpan di database sebelum
 * perubahan konstanta LaporanKendala::STATUS_MENUNGGU_TEMBUSAN.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('laporan_kendalas')
            ->where('status', 'Menunggu Tembusan')
            ->update(['status' => 'Menunggu Balasan']);
    }

    public function down(): void
    {
        DB::table('laporan_kendalas')
            ->where('status', 'Menunggu Balasan')
            ->update(['status' => 'Menunggu Tembusan']);
    }
};
