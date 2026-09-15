<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fondasi "Disposisi & Tindakan" khusus alur surat Danpus (lihat
     * LaporanSurat::DISPOSISI_DANPUS_OPTIONS & TINDAKAN_DANPUS_OPTIONS,
     * serta form Buat Surat Baru di laporan-danpus.blade.php).
     * Kedua kolom SENGAJA nullable -- satuan lain (Kasansi, Satlak, Sdir,
     * Urdal, Pok Analis, Wadan, dst) TETAP pakai form lama tanpa Disposisi
     * & Tindakan, jadi baris surat mereka akan NULL di dua kolom ini.
     *
     * - disposisi: satu nilai string (siapa yang harus menangani surat).
     * - tindakan : disimpan JSON array of string (bisa pilih lebih dari
     *              satu tindakan sekaligus, lihat checkbox di form).
     */
    public function up(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->string('disposisi')->nullable()->after('prioritas');
            $table->json('tindakan')->nullable()->after('disposisi');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dropColumn(['disposisi', 'tindakan']);
        });
    }
};
