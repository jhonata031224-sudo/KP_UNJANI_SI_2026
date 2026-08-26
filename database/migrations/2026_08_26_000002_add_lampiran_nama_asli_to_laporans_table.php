<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storage::store() bikin nama file acak (hash) buat lampiran_path --
     * bagus buat hindari bentrok nama, tapi gak enak ditunjukin ke user
     * sebagai "nama file". Kolom ini simpan nama file ASLI yang diupload
     * (getClientOriginalName()), khusus buat ditampilkan di UI (link
     * "lampiran saat ini" pas mode Edit Progres) -- lampiran_path sendiri
     * tetap yang dipakai buat path fisik di storage/URL, tidak berubah.
     */
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->string('lampiran_nama_asli')->nullable()->after('lampiran_path');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn('lampiran_nama_asli');
        });
    }
};
