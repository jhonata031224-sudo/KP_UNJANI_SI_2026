<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama kayak lampiran_nama_asli di tabel laporans (lihat migration
     * 2026_08_26_000002) -- Storage::store() bikin nama file acak (hash)
     * buat lampiran_path, kolom ini simpan nama file ASLI yang diupload
     * (getClientOriginalName()) khusus buat ditampilkan di UI (modal Detail
     * Surat). lampiran_path tetap yang dipakai buat path fisik/URL.
     */
    public function up(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->string('lampiran_nama_asli')->nullable()->after('lampiran_path');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dropColumn('lampiran_nama_asli');
        });
    }
};
