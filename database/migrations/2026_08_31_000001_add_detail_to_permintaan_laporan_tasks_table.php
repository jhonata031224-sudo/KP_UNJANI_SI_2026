<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "detail" per task pada Permintaan Laporan -- penjelasan lengkap
 * tiap task yang wajib diisi Pimpinan di modal "Buat Permintaan Laporan"
 * (di samping "deskripsi" yang jadi judul singkat task). Nullable supaya
 * task lama yang dibuat sebelum fitur ini tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_laporan_tasks', function (Blueprint $table) {
            $table->text('detail')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_laporan_tasks', function (Blueprint $table) {
            $table->dropColumn('detail');
        });
    }
};
