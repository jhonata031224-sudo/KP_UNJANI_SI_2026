<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "Kategori" (opsional) sudah ada di form Kirim Laporan Kasansi sejak
 * awal, tapi tabel laporan_kendalas belum pernah punya kolomnya -- akibatnya
 * input kategori yang diisi Kasansi selalu dibuang diam-diam saat submit
 * (tidak divalidasi & tidak disimpan di LaporanKendalaController::store()),
 * makanya selalu tampil kosong ('-') di modal Detail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->string('kategori')->nullable()->after('perihal');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kendalas', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
